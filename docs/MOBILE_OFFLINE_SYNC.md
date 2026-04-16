# Mobile offline sync protocol (m-engine)

## Goals

- Allow mobile clients to cache **calendar**, **kanban**, and **ad drafts** while offline.
- Reconcile with the server when connectivity returns using **versioned manifests** and **per-collection cursors**.

## Authentication

- All sync endpoints require `Authorization: Bearer <sanctum_personal_access_token>` (same as primary API).
- Integration tokens (`/api/integration/v1/*`) are intended for **external systems**, not consumer mobile apps.

## Manifest endpoint

`GET /api/mobile/v1/sync/manifest`

Optional query:

- `since` — ISO-8601 timestamp; server returns items with `updated_at` greater than this value when applicable.

Response shape (versioned):

```json
{
  "api_version": "mobile_sync_v1",
  "server_time": "2026-04-16T12:00:00+00:00",
  "collections": [
    {
      "name": "search_request_drafts",
      "cursor_field": "updated_at",
      "items": []
    },
    {
      "name": "conversations",
      "cursor_field": "updated_at",
      "items": []
    },
    {
      "name": "calendar_events",
      "cursor_field": "updated_at",
      "items": []
    }
  ],
  "hints": {}
}
```

## Collections

### `search_request_drafts`

- Rows where `created_by_user_id = current user` and `ad_status = draft`.
- Client should persist the max `updated_at` as the next `since` cursor after a successful pull.

### `conversations`

- User-scoped conversation snapshots used for offline messenger list rendering.
- Include only conversations where current user is a participant.

### `calendar_events`

- User-scoped calendar events (`user_id = current user`) for offline calendar timeline.
- Merge strategy is cursor-based by `updated_at`.

### Calendar / Kanban (client-side caching)

- There is no single “blob” endpoint yet: mobile apps should call existing authenticated music/calendar/kanban APIs and persist JSON responses in SQLite/Room.
- On reconnect, **re-fetch** affected ranges (time window) and merge using `updated_at` / `id` monotonic rules.

## Calendar sync helper endpoints

- `GET /api/music/calendar-sync/connectors`
  - returns connector readiness statuses (`google`, `outlook`, `ical`).
- `GET /api/music/calendar-sync/feed?start=<ISO>&end=<ISO>`
  - returns booking/event feed optimized for sync ranges.

## Conflict resolution

1. **Last-write-wins (LWW)** for drafts when the server has not published the ad (`draft` only).
2. If the server rejected a draft (validation / moderation), client must **discard local edits** and show server error payload.
3. For kanban card moves, prefer **server ordering** after merge; if both sides moved a card, prompt user or apply LWW on `updated_at`.

## Kotlin / cross-platform roadmap

- Short term: extend `/mobileplatform` Kotlin app using this manifest + existing REST routes.
- Medium term: evaluate **Flutter** or **React Native** for iOS + Android parity; keep API contracts stable.

## Rate limits

- Manifest route is throttled (`throttle:60,1` per user session). Batch pulls; avoid polling sub-second.
- Calendar sync endpoints inherit authenticated API throttles; use incremental pulls by time windows.
