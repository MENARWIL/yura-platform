<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

/**
 * Páginas de confianza del portal público (por ahora, solo Privacidad).
 */
class LegalController extends Controller
{
    /**
     * Fecha de la última revisión del texto de privacidad (AAAA-MM-DD).
     * Se actualiza a mano cuando cambia el contenido de los archivos privacy.php de resources/lang (es y qu).
     */
    public const PRIVACY_UPDATED_AT = '2026-10-25';

    public function privacy()
    {
        return view('public.privacy', [
            'updatedAt' => Carbon::parse(self::PRIVACY_UPDATED_AT),
        ]);
    }
}
