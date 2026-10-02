declare namespace App {
    namespace DTO {
        export type AuthUserData = {
            id: number;
            name: string;
            isAdmin: boolean;
        };
        export type CheckResultData = {
            exitCode: number;
            output: string;
        };
        export type CommentData = {
            commentable_type: App.Enums.CommentableType;
            commentable_id: number;
            content: string;
            parent_id?: number | null;
        };
        namespace Admin {
            export type ExportData = {
                type: string;
            };
            export type UpdateUserData = {
                name: string;
                github_name: string | null;
                is_admin: boolean | null;
            };
        }
        namespace Api {
            export type CheckSolutionData = {
                solution_code: string;
                user_id: number | null;
            };
            export type SaveSolutionData = {
                user_id: number;
                solution_code: string;
            };
        }
        namespace Navigation {
            export type LocaleLinkData = {
                code: string;
                label: string;
                flagUrl: string;
                href: string;
            };
            export type NavItemData = {
                label: string;
                href: string;
                active: boolean;
                inertia: boolean;
                method: string | null;
                icon: string | null;
                children: App.DTO.Navigation.NavItemData[];
            };
            export type NavSectionData = {
                title: string | null;
                items: App.DTO.Navigation.NavItemData[];
            };
            export type NavigationData = {
                homeUrl: string;
                logoUrl: string;
                logoAlt: string;
                main: App.DTO.Navigation.NavItemData[];
                user: App.DTO.Navigation.NavItemData[];
                currentLocale: App.DTO.Navigation.LocaleLinkData;
                otherLocales: App.DTO.Navigation.LocaleLinkData[];
                footer: App.DTO.Navigation.NavSectionData[];
            };
        }
        namespace Progress {
            export type ChapterProgressData = {
                chapter: undefined;
                hasChildren: boolean;
                isCompleted: boolean;
                childrenProgress: undefined | null;
                exercisesProgress: undefined | null;
            };
            export type ExerciseProgressData = {
                exercise: undefined;
                exerciseMember: undefined | null;
            };
        }
        namespace Settings {
            export type AccountPageData = {
                email: string;
                resetPasswordUrl: string;
                destroyUrl: string;
                menu: App.DTO.Navigation.NavItemData[];
            };
            export type ProfilePageData = {
                name: string;
                email: string;
                github_name: string | null;
                profileImage: string;
                updateUrl: string;
                menu: App.DTO.Navigation.NavItemData[];
            };
            export type ProfileUpdateData = {
                name: string;
                github_name: string | null;
            };
        }
    }
    namespace Enums {
        export type CommentableType =
            "App\\Models\\Chapter" | "App\\Models\\Exercise";
    }
}
declare namespace Illuminate {
    export type CursorPaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            path: string;
            per_page: number;
            next_cursor: string | null;
            next_page_url: string | null;
            prev_cursor: string | null;
            prev_page_url: string | null;
        };
    };
    export type CursorPaginatorInterface<TKey, TValue> =
        Illuminate.CursorPaginator<TKey, TValue>;
    export type LengthAwarePaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            total: number;
            current_page: number;
            first_page_url: string;
            from: number | null;
            last_page: number;
            last_page_url: string;
            next_page_url: string | null;
            path: string;
            per_page: number;
            prev_page_url: string | null;
            to: number | null;
        };
    };
    export type LengthAwarePaginatorInterface<TKey, TValue> =
        Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
    namespace LaravelData {
        export type CursorPaginatedDataCollection<TKey, TValue> =
            Illuminate.CursorPaginator<TKey, TValue>;
        export type PaginatedDataCollection<TKey, TValue> =
            Illuminate.LengthAwarePaginator<TKey, TValue>;
    }
}
