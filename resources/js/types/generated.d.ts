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
        export type PaginationData = {
            currentPage: number;
            lastPage: number;
            total: number;
            links: App.DTO.PaginationLinkData[];
        };
        export type PaginationLinkData = {
            url: string | null;
            label: string;
            active: boolean;
        };
        namespace Activity {
            export type ActivityItemData = {
                id: number;
                causerName: string | null;
                causerUrl: string | null;
                description: string;
                links: App.DTO.Activity.ActivityLinkData[];
                createdAt: string;
            };
            export type ActivityLinkData = {
                label: string;
                href: string | null;
            };
            export type ActivityPageData = {
                items: App.DTO.Activity.ActivityItemData[];
                pagination: App.DTO.PaginationData;
            };
        }
        namespace Admin {
            export type ExportData = {
                type: string;
            };
            export type ExportPageData = {
                types: App.DTO.Admin.ExportTypeData[];
                storeUrl: string;
                menu: App.DTO.Navigation.NavItemData[];
            };
            export type ExportTypeData = {
                value: string;
                label: string;
            };
            export type UpdateUserData = {
                name: string;
                github_name: string | null;
                is_admin: boolean | null;
            };
            export type UserEditPageData = {
                name: string;
                githubName: string | null;
                isAdmin: boolean;
                updateUrl: string;
                cancelUrl: string;
                menu: App.DTO.Navigation.NavItemData[];
            };
            export type UserFilterData = {
                name: string | null;
                email: string | null;
            };
            export type UserListItemData = {
                id: number;
                name: string;
                email: string;
                isAdmin: boolean;
                createdAt: string;
                showUrl: string;
                editUrl: string;
            };
            export type UserListPageData = {
                items: App.DTO.Admin.UserListItemData[];
                pagination: App.DTO.PaginationData;
                filter: App.DTO.Admin.UserFilterData;
                filterUrl: string;
                menu: App.DTO.Navigation.NavItemData[];
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
            export type ChapterNodeData = {
                id: number;
                title: string;
                url: string;
                isCompleted: boolean;
                completedChildrenCount: number;
                totalChildrenCount: number;
                children: App.DTO.Progress.ChapterNodeData[];
                exercises: App.DTO.Progress.ExerciseNodeData[];
            };
            export type ExerciseNodeData = {
                id: number;
                title: string;
                url: string;
                isCompleted: boolean;
                isInProgress: boolean;
            };
            export type MyProgressPageData = {
                userName: string;
                userUrl: string;
                solutionsUrl: string;
                chapters: App.DTO.Progress.ChapterNodeData[];
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
        namespace Solution {
            export type SolutionShowPageData = {
                title: string;
                exerciseTitle: string;
                exerciseUrl: string;
                userName: string;
                userUrl: string;
                versions: App.DTO.Solution.SolutionVersionData[];
            };
            export type SolutionVersionData = {
                id: number;
                content: string;
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
