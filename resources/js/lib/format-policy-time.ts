/**
 * Formatea una fecha ISO 8601 (con TZ) en formato amigable según la TZ del navegador.
 *
 * Si el usuario está en horario México (UTC-6), muestra solo la hora oficial.
 * Si está en otra TZ, muestra "{hora local} (hora local) · {hora México} México".
 */

const OFFICIAL_TZ = 'America/Mexico_City';

function getOffsetMinutes(tz: string, atIso: string): number {
    const date = new Date(atIso);
    // Usamos Intl para extraer la hora "como sería" en la TZ dada y comparamos con UTC.
    const formatter = new Intl.DateTimeFormat('en-US', {
        timeZone: tz,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });
    const parts = formatter.formatToParts(date);
    const get = (type: string) => Number(parts.find((p) => p.type === type)?.value ?? 0);
    const tzDate = new Date(
        Date.UTC(get('year'), get('month') - 1, get('day'), get('hour') === 24 ? 0 : get('hour'), get('minute'), get('second'))
    );
    return Math.round((tzDate.getTime() - date.getTime()) / 60000);
}

function isUserInOfficialTz(atIso: string): boolean {
    const userTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (userTz === OFFICIAL_TZ) return true;
    // Comparar offset: si en este instante coinciden offsets, mostramos solo la hora "oficial".
    return getOffsetMinutes(userTz, atIso) === getOffsetMinutes(OFFICIAL_TZ, atIso);
}

function formatInTz(atIso: string, tz: string): string {
    const date = new Date(atIso);
    const formatter = new Intl.DateTimeFormat('es-MX', {
        timeZone: tz,
        weekday: 'long',
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
    // Intl devuelve algo como "lunes, 15 jun, 08:00" — limpiamos comas dobles
    return formatter.format(date).replace(/,\s*/g, ' ').replace(/\.\s*$/, '').trim();
}

/**
 * Formatea para mostrar en UI con conversión a hora local + nota México.
 *
 * Ejemplos:
 * - CDMX: "lunes 15 jun 08:00"
 * - Tijuana: "lunes 15 jun 07:00 (hora local) · 08:00 México"
 * - Madrid: "lunes 15 jun 16:00 (hora local) · 08:00 México"
 */
export function formatPolicyTime(atIso: string | null): string {
    if (!atIso) return '—';
    const officialText = formatInTz(atIso, OFFICIAL_TZ);
    if (isUserInOfficialTz(atIso)) {
        return officialText;
    }
    const userTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const localText = formatInTz(atIso, userTz);
    // Extraer solo "HH:MM" del officialText para hacerlo más compacto
    const officialTimeMatch = officialText.match(/(\d{2}:\d{2})/);
    const officialTime = officialTimeMatch ? officialTimeMatch[1] : officialText;
    return `${localText} (hora local) · ${officialTime} México`;
}

/**
 * Versión compacta sin fecha (solo hora) para tooltips o lugares donde la fecha ya se mostró.
 */
export function formatPolicyTimeShort(atIso: string | null): string {
    if (!atIso) return '—';
    const formatTime = (tz: string) =>
        new Intl.DateTimeFormat('es-MX', {
            timeZone: tz,
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        }).format(new Date(atIso));

    const officialTime = formatTime(OFFICIAL_TZ);
    if (isUserInOfficialTz(atIso)) {
        return officialTime;
    }
    const userTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
    return `${formatTime(userTz)} local · ${officialTime} México`;
}
