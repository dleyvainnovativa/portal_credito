/*
|--------------------------------------------------------------------------
| pdf-split.js — Client-side PDF splitting (pdf-lib)
|--------------------------------------------------------------------------
| Splits a PDF into valid parts that satisfy BOTH limits:
|   1. Page limit  — Contisign accepts at most MAX_PAGES (90) pages per annex.
|   2. Byte limit  — each part must be under maxBytes (the 15 MB annex limit).
|
| Strategy (fast):
|   - Primary split is by PAGE COUNT — slice into <=MAX_PAGES chunks. This is
|     cheap and deterministic (no serialization needed to decide boundaries).
|     e.g. 200 pages, 10 MB total -> 90 / 90 / 20 purely on page count.
|   - Each page-chunk is saved ONCE and its size checked. Only if it exceeds
|     maxBytes do we split it further — and we do that by PROPORTIONAL
|     ESTIMATION (estimate pages-per-part from the measured bytes/page), not by
|     adding one page at a time. This turns the old O(pages^2) greedy probing
|     (which re-saved the whole doc per page and caused ~8 min on 1200 pages)
|     into a handful of saves per chunk.
|
| A single page that alone exceeds maxBytes cannot be split further -> throw.
|
| Usage:
|   import { splitPdfIfNeeded } from './pdf-split';
|   const parts = await splitPdfIfNeeded(file, maxBytes); // File[] (1+ parts)
*/

import { PDFDocument } from 'pdf-lib';

// Contisign hard limit: max pages per annexed document.
const MAX_PAGES = 90;

/**
 * @param {File} file
 * @param {number} maxBytes
 * @returns {Promise<File[]>} one File if within both limits, else N parts
 */
export async function splitPdfIfNeeded(file, maxBytes) {
    const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    // Non-PDFs can't be page-split. Only the size limit applies.
    if (!isPdf) {
        if (file.size > maxBytes) throw new OversizeError(file.name);
        return [file];
    }

    const bytes = new Uint8Array(await file.arrayBuffer());
    const src = await PDFDocument.load(bytes, { ignoreEncryption: true });
    const pageCount = src.getPageCount();

    // Fast path: already within BOTH limits -> no work.
    if (pageCount <= MAX_PAGES && file.size <= maxBytes) {
        return [file];
    }

    const baseName = file.name.replace(/\.pdf$/i, '');

    // 1) Primary split by page count into <=MAX_PAGES ranges. Each range is
    //    [start, endInclusive].
    /** @type {Array<[number, number]>} */
    const pageRanges = [];
    for (let start = 0; start < pageCount; start += MAX_PAGES) {
        pageRanges.push([start, Math.min(start + MAX_PAGES - 1, pageCount - 1)]);
    }

    // 2) For each page range, save once; if over maxBytes, sub-split by
    //    proportional estimation. Collect final blobs in order.
    const blobs = [];
    for (const [start, end] of pageRanges) {
        await splitRangeByBytes(src, start, end, maxBytes, blobs);
    }

    // 3) Wrap blobs as named File parts.
    return blobs.map((blob, i) =>
        blobToFile(blob, `${baseName}-parte${i + 1}.pdf`)
    );
}

/**
 * Save the page range [start,end] as one PDF. If it fits maxBytes, push it.
 * Otherwise estimate how many pages fit and recurse on the halves, using the
 * measured bytes-per-page to pick a good split point (few saves, not per-page).
 *
 * @param {PDFDocument} src
 * @param {number} start inclusive
 * @param {number} end   inclusive
 * @param {number} maxBytes
 * @param {Blob[]} out
 */
async function splitRangeByBytes(src, start, end, maxBytes, out) {
    const blob = await buildRange(src, start, end);

    if (blob.size <= maxBytes) {
        out.push(blob);
        return;
    }

    const pages = end - start + 1;

    // A single page over the limit can't be split further.
    if (pages === 1) {
        throw new OversizeError('document');
    }

    // Estimate how many pages fit under maxBytes, with a safety margin so we
    // don't overshoot and need another pass. bytesPerPage from this measured
    // blob is a good predictor for this region of the document.
    const bytesPerPage = blob.size / pages;
    let fit = Math.floor((maxBytes * 0.9) / bytesPerPage);

    // Clamp to a sane range: at least 1, at most pages-1 (so we always make
    // progress and never re-create the same range).
    fit = Math.max(1, Math.min(fit, pages - 1));

    const mid = start + fit - 1; // inclusive end of the first sub-range
    await splitRangeByBytes(src, start, mid, maxBytes, out);
    await splitRangeByBytes(src, mid + 1, end, maxBytes, out);
}

/** Build a PDF blob from an inclusive page range of the source document. */
async function buildRange(src, start, end) {
    const out = await PDFDocument.create();
    const indices = [];
    for (let i = start; i <= end; i++) indices.push(i);
    const copied = await out.copyPages(src, indices);
    copied.forEach((p) => out.addPage(p));
    const outBytes = await out.save();
    return new Blob([outBytes], { type: 'application/pdf' });
}

function blobToFile(blob, name) {
    return new File([blob], name, { type: 'application/pdf', lastModified: Date.now() });
}

export class OversizeError extends Error {
    constructor(fileName) {
        super(`File "${fileName}" is too large and cannot be split (a single page exceeds the limit).`);
        this.name = 'OversizeError';
        this.fileName = fileName;
    }
}