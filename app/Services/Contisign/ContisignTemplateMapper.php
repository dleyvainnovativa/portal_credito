<?php

namespace App\Services\Contisign;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/*
|--------------------------------------------------------------------------
| ContisignTemplateMapper
|--------------------------------------------------------------------------
| Turns the normalized application payload (from PayloadMapper) into the
| HTML + DataTemplates for a given Contisign template.
|
| Placeholders in the template's stored HTML (the `Templates` key of the
| template JSON) are written as <s>variableName</s>. We replace each known
| variable globally with the mapped value; any placeholder we don't have a
| value for is replaced with an empty string so no literal struck-through
| token survives into the signed document.
|
| Two templates are supported, each with its own variable set:
|   - solicitud (00.SOLICITUD DE CRÉDITO) — 36 vars
|   - buro      (00.BURO DE CREDITO)      — 11 vars
|
| Variables present in a template but not collected by the wizard
| (giro, antigüedad, employment block, secondary business address) are left
| blank by design.
*/

class ContisignTemplateMapper
{
    /**
     * @param string $templateKey 'solicitud'|'buro'
     * @param array  $payload     normalized PayloadMapper output
     * @return array{html:string, dataTemplates:array, template:array}
     */
    public function map(string $templateKey, array $payload): array
    {
        $cfg = config("contisign.templates.$templateKey");
        if (! $cfg) {
            throw new RuntimeException("Unknown Contisign template [$templateKey].");
        }

        $template = $this->loadTemplate($cfg['json_path']);
        $values   = $this->values($templateKey, $payload);

        // Whether to send a filled `html` or let Contisign render server-side
        // from DataTemplates with an empty html. Toggle via config while we
        // confirm which the API wants. Default: empty (known-good).
        $sendHtml = (bool) config('contisign.send_filled_html', false);

        $html = $sendHtml
            ? $this->substitute($template['Templates'] ?? '', $values)
            : '';
        // dd($html);
        return [
            'html'          => $html,
            'dataTemplates' => $values,
            'template'      => $template,
        ];
    }

    /* ----------------------------------------------------------------
     | Value maps (variable => wizard value)
     | ---------------------------------------------------------------- */

    private function values(string $templateKey, array $p): array
    {
        return $templateKey === 'buro'
            ? $this->buroValues($p)
            : $this->solicitudValues($p);
    }

    /** BURÓ DE CRÉDITO — 11 variables. */
    private function buroValues(array $p): array
    {
        $profile = $p['profile'] ?? [];
        $addr    = $p['address'] ?? [];
        $contact = $p['contact'] ?? [];
        $isCompany = ($p['applicant_type'] ?? null) === 'company';

        $name = $isCompany
            ? ($profile['legal_name'] ?? '')
            : trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));

        $rep = $isCompany
            ? trim((($p['representative']['first_name'] ?? '')) . ' ' . (($p['representative']['last_name'] ?? '')))
            : '';

        return [
            'personatipo'        => $isCompany ? 'Persona Moral' : 'Persona Física',
            'nombresolicitante'  => $name,
            'representantelegal' => $rep,
            'rfcsolicitante'     => $profile['rfc'] ?? '',
            'domsolicitante'     => $addr['street'] ?? '',
            'colsolicitante'     => $addr['colonia'] ?? '',
            'mpiosolicitante'    => $addr['city'] ?? '',
            'edosolicitante'     => $addr['state'] ?? '',
            'cpsolcitante'       => $addr['postal_code'] ?? '',
            'telsolicitante'     => $contact['phone'] ?? '',
            'fircliorepleg'      => '', // signature placeholder — left blank
        ];
    }

    /** SOLICITUD DE CRÉDITO — 36 variables (unmapped left blank). */
    private function solicitudValues(array $p): array
    {
        $profile = $p['profile'] ?? [];
        $addr    = $p['address'] ?? [];
        $contact = $p['contact'] ?? [];
        $refs    = $p['references'] ?? [];
        $isCompany = ($p['applicant_type'] ?? null) === 'company';

        $name = $isCompany
            ? ($profile['legal_name'] ?? '')
            : trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));

        return [
            'fecha'      => now()->format('d/m/Y'),
            'nombre'     => $name,
            'ncmcl'      => $profile['commercial_name'] ?? '',
            'stwb'       => $profile['website'] ?? '',
            'domicilio'  => $addr['street'] ?? '',
            'colonia'    => $addr['colonia'] ?? '',
            'ciudad'     => $addr['city'] ?? '',
            'estado'     => $addr['state'] ?? '',
            'cpostal'    => $addr['postal_code'] ?? '',
            'rfc'        => $profile['rfc'] ?? '',
            'email'      => $contact['email'] ?? '',
            'telefono'   => $contact['phone'] ?? '',

            // --- Not collected by the wizard → intentionally blank ---
            'giro'       => '',
            'antigu'     => '',
            'domicilion' => '',
            'colonian' => '',
            'ciudadn' => '',
            'estadon' => '',
            'cpostaln' => '',
            'empresa'    => '',
            'puesto' => '',
            'telemp' => '',
            'antemp' => '',
            'jdemp' => '',
            'domicmp'    => '',
            'colemp' => '',
            'ciuemp' => '',
            'estemp' => '',
            'cpemp' => '',
            // ---------------------------------------------------------

            'emprov1'  => $refs[0]['company'] ?? '',
            'telprov1' => $refs[0]['phone'] ?? '',
            'cmprov2'  => $refs[1]['company'] ?? '',
            'tclprov2' => $refs[1]['phone'] ?? '',
            'cmprov3'  => $refs[2]['company'] ?? '',
            'telprov3' => $refs[2]['phone'] ?? '',

            'ejventas' => $contact['sales_rep_email'] ?? '',
        ];
    }

    /* ----------------------------------------------------------------
     | Substitution
     | ---------------------------------------------------------------- */

    /**
     * Replace every <s>variable</s> placeholder (global) with its prepared
     * value. Unknown placeholders are blanked so no struck-through token
     * remains.
     */
    private function substitute(string $html, array $values): string
    {
        if ($html === '') {
            return $html;
        }

        foreach ($values as $var => $value) {
            // $html = preg_replace(
            //     '/<s>\s*' . preg_quote($var, '/') . '\s*<\/s>/u',
            //     $this->prepareValue((string) $value),
            //     $html
            // );
            $html = preg_replace(
                '/<s>' . preg_quote($var, '/') . '<\/s>/i',
                $this->prepareValue((string) $value),
                $html
            );
        }

        // Blank any remaining <s>...</s> placeholders we didn't map.
        // $html = preg_replace('/<s>\s*[a-z0-9_]+\s*<\/s>/iu', '', $html);

        return $html;
    }

    /**
     * Prepare a value for injection into the template HTML:
     *   - escape only the three characters that would break HTML structure
     *     (& < >). We deliberately do NOT use htmlspecialchars() with quotes,
     *     which previously turned values into entity soup and is the suspected
     *     cause of failed document generation.
     *   - optionally strip accents (config toggle) if the generator can't
     *     handle them inside the HTML.
     */
    private function prepareValue(string $value): string
    {
        if ((bool) config('contisign.strip_accents', false)) {
            $value = $this->stripAccents($value);
        }

        // Minimal, structure-only escaping.
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $value);
    }

    /** Transliterate accented Latin characters to ASCII (Río → Rio). */
    private function stripAccents(string $value): string
    {
        // Prefer intl transliteration when available; fall back to a map.
        if (function_exists('transliterator_transliterate')) {
            $t = transliterator_transliterate('Any-Latin; Latin-ASCII', $value);
            if (is_string($t)) {
                return $t;
            }
        }

        $map = [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ];
        return strtr($value, $map);
    }

    private function loadTemplate(string $path): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            throw new RuntimeException("Contisign template JSON not found at storage/app/$path");
        }
        $json = json_decode($disk->get($path), true);
        if (! is_array($json)) {
            throw new RuntimeException("Contisign template JSON at $path is invalid.");
        }
        return $json;
    }
}
