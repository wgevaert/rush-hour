export async function callApiEndpoint(
    params: Record<string, string>,
    abortSignal?: AbortSignal
): Promise<any> {
    return await fetch("/api.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(params),
        signal: abortSignal,
    }).then(response => response.json());
}

export function setUrlParam(key: string, value: string) {
    const location = new URL(window.location.toString());
    const searchParams = new URLSearchParams(location.search);
    if (searchParams.has(key)) {
        const allParams = searchParams.getAll(key);
        if (allParams.length === 1 && allParams[0] === value) return;
    }
    searchParams.set(key, value);
    location.search = searchParams.toString();
    history.pushState({}, "", location);
}

export function getUrlParam(key:string):string|null {
    const searchParams = new URLSearchParams(location.search);
    if (!searchParams.has(key)){
        return null;
    }
    return searchParams.get(key);
}