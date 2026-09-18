export class ApiError extends Error {
	status: number
	data: unknown

	constructor(status: number, data: unknown) {
		super(`Request failed with status ${status}`)
		this.name = "ApiError"
		this.status = status
		this.data = data
	}
}

function readCookie(name: string): string | null {
	const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`))
	return match ? decodeURIComponent(match[1]) : null
}

type ApiFetchOptions = {
	method?: "GET" | "POST" | "PUT" | "PATCH" | "DELETE"
	body?: unknown
	params?: Record<string, string | number | boolean | undefined>
	signal?: AbortSignal
}

async function apiFetch<T = unknown>(
	url: string,
	{ method = "GET", body, params, signal }: ApiFetchOptions = {},
): Promise<T> {
	const target = new URL(url, window.location.origin)
	if (params) {
		for (const [key, value] of Object.entries(params)) {
			if (value !== undefined) target.searchParams.set(key, String(value))
		}
	}

	const headers: Record<string, string> = {
		Accept: "application/json",
	}

	if (body !== undefined) {
		headers["Content-Type"] = "application/json"
	}

	if (method !== "GET") {
		const token = readCookie("XSRF-TOKEN")
		if (token) headers["X-XSRF-TOKEN"] = token
	}

	const response = await fetch(target, {
		method,
		headers,
		credentials: "same-origin",
		body: body !== undefined ? JSON.stringify(body) : undefined,
		signal,
	})

	const contentType = response.headers.get("content-type") ?? ""
	const data = contentType.includes("application/json")
		? await response.json()
		: undefined

	if (!response.ok) {
		throw new ApiError(response.status, data)
	}

	return data as T
}

export const api = {
	get: <T = unknown>(
		url: string,
		options?: Omit<ApiFetchOptions, "method" | "body">,
	) => apiFetch<T>(url, { ...options, method: "GET" }),
	post: <T = unknown>(
		url: string,
		body?: unknown,
		options?: Omit<ApiFetchOptions, "method" | "body">,
	) => apiFetch<T>(url, { ...options, method: "POST", body }),
}
