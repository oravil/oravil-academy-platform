// v0.1 is single-module scope (OA-MVP-007 exposes no "list modules" or
// "current module" endpoint). The module id is DB-generated
// (gen_random_uuid), so a literal baked into source is not portable across
// environments/reseeds. Sourced from VITE_MODULE_ID, defaulting to this
// environment's seeded Module 1 id — flagged per OA-HANDOFF-001 Task 7
// Phase C report.
export const CURRENT_MODULE_ID: string =
  import.meta.env.VITE_MODULE_ID ?? '48cba916-5ca8-436c-9c80-9b77b7a95c9a'
