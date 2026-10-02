declare namespace App {
    namespace DTO {
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
