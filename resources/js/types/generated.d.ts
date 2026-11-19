declare namespace App {
namespace Data {
export type AuthData = {
user: App.Data.AuthUserData | null,
};
export type AuthUserData = {
id: number,
vatsim_id: number | null,
name: string,
first_name: string | null,
last_name: string | null,
email: string | null,
rating: number,
subdivision: string | null,
is_staff: boolean,
is_superuser: boolean,
is_admin: boolean,
is_vatsim_user: boolean,
roles: string[],
is_mentor: boolean,
is_leadership: boolean,
is_leading_mentor: boolean,
};
export type FlashData = {
success: string | null,
error: string | null,
};
export type SharedData = {
name: string,
auth: App.Data.AuthData,
sidebarOpen: boolean,
theme: string,
flash: App.Data.FlashData,
};
}
}
declare namespace Illuminate {
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
namespace LaravelData {
export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
}
