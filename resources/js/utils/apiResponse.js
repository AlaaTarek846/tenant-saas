export function unwrapApiData(response) {
    const body = response?.data;

    if (body && typeof body === 'object' && body.data !== undefined && (body.status === 'success' || body.code === true)) {
        return body.data;
    }

    return body;
}

export function unwrapApiMessage(response) {
    const body = response?.data;

    return body?.message ?? '';
}
