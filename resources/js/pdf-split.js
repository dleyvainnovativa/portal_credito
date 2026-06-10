/*
|--------------------------------------------------------------------------
| pdf-split.js — Client-side PDF splitting (pdf-lib)
|--------------------------------------------------------------------------
| Splits a PDF that exceeds the annex size limit into multiple valid PDFs,
| each under the limit, by distributing pages across parts. Runs in the
| browser so the large original never hits PHP upload limits and so modern
| PDF versions (1.5+) are handled natively (pdf-lib has no version ceiling,
| unlike FPDI).
|
| Strategy:
|   - A PDF can only be split at PAGE boundaries (byte-splitting corrupts it).
|   - We estimate a per-part page budget, build each part, and if a part still
|     exceeds the limit we reduce pages and retry.
|   - If a SINGLE page alone exceeds the limit, it cannot be split further →
|     we throw, and the UI surfaces the "upload a smaller file" error.
|
| Usage:
|   import { splitPdfIfNeeded } from './pdf-split';
|   const parts = await splitPdfIfNeeded(file, maxBytes); // File[] (1+ parts)
*/

import { PDFDocument } from 'pdf-lib';

/**
 * @param {File} file
 * @param {number} maxBytes
 * @returns {Promise<File[]>} one File if under limit, else N split parts
 */
export async function splitPdfIfNeeded(file, maxBytes) {
    // Only PDFs are splittable. Images over the limit can't be split here.
    const isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name);
    if (!isPdf) {
        if (file.size > maxBytes) {
            throw new OversizeError(file.name);
        }
        return [file];
    }

    if (file.size <= maxBytes) {
        return [file];
    }

    const bytes = new Uint8Array(await file.arrayBuffer());
    const src = await PDFDocument.load(bytes, { ignoreEncryption: true });
    const pageCount = src.getPageCount();

    if (pageCount <= 1) {
        // One page that's already too big — nothing we can do client-side.
        throw new OversizeError(file.name);
    }

    const baseName = file.name.replace(/\.pdf$/i, '');
    const parts = [];

    // Greedy page packing: grow a part page-by-page until adding another page
    // would exceed the limit, then seal it and start the next.
    let startPage = 0;
    while (startPage < pageCount) {
        let endPage = startPage; // inclusive
        let lastGoodBlob = null;
        let lastGoodEnd = -1;

        // Expand the range as far as possible while staying under maxBytes.
        while (endPage < pageCount) {
            const blob = await buildPart(src, startPage, endPage);

            if (blob.size <= maxBytes) {
                lastGoodBlob = blob;
                lastGoodEnd = endPage;
                endPage++;
            } else {
                break;
            }
        }

        if (!lastGoodBlob) {
            // Even the single startPage exceeds the limit.
            throw new OversizeError(file.name);
        }

        const partIndex = parts.length + 1;
        parts.push(blobToFile(lastGoodBlob, `${baseName}-parte${partIndex}.pdf`));
        startPage = lastGoodEnd + 1;
    }

    return parts;
}

/** Build a PDF blob from an inclusive page range of the source document. */
async function buildPart(src, startPage, endPage) {
    const out = await PDFDocument.create();
    const indices = [];
    for (let i = startPage; i <= endPage; i++) indices.push(i);
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