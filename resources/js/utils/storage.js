/**
 * Safe localStorage helpers with JSON serialization.
 */
export function getStorageItem(key, defaultValue = null) {
    try {
        const raw = localStorage.getItem(key);

        if (raw === null) {
            return defaultValue;
        }

        return JSON.parse(raw);
    } catch {
        return defaultValue;
    }
}

export function setStorageItem(key, value) {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch {
        // Storage quota exceeded or unavailable — fail silently.
    }
}

export function removeStorageItem(key) {
    try {
        localStorage.removeItem(key);
    } catch {
        // Fail silently.
    }
}

export function clearStorageItems(keys) {
    keys.forEach((key) => removeStorageItem(key));
}
