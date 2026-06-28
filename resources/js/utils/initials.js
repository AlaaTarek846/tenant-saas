/**
 * First two characters from a display name for avatar fallbacks.
 * Uses first letter of first two words when available.
 */
export function getNameInitials(name, length = 2) {
    const trimmed = name?.trim();

    if (!trimmed) {
        return '?';
    }

    const parts = trimmed.split(/\s+/).filter(Boolean);

    if (parts.length >= 2) {
        return `${parts[0].charAt(0)}${parts[1].charAt(0)}`.slice(0, length);
    }

    return trimmed.slice(0, length);
}

/**
 * Stable soft background from the name string.
 */
export function getInitialsColor(name) {
    const palette = [
        { bg: 'rgba(85, 110, 230, 0.18)', color: '#556ee6' },
        { bg: 'rgba(52, 195, 143, 0.18)', color: '#34c38f' },
        { bg: 'rgba(241, 180, 76, 0.18)', color: '#f1b44c' },
        { bg: 'rgba(244, 106, 106, 0.18)', color: '#f46a6a' },
        { bg: 'rgba(80, 165, 241, 0.18)', color: '#50a5f1' },
        { bg: 'rgba(111, 66, 193, 0.18)', color: '#6f42c1' },
    ];

    const source = name?.trim() || '?';
    let hash = 0;

    for (let i = 0; i < source.length; i += 1) {
        hash = source.charCodeAt(i) + ((hash << 5) - hash);
    }

    return palette[Math.abs(hash) % palette.length];
}
