# The Intake Interview

The quality of the whole document is decided here. Sections are only as good as what you learned, so
the interview is deliberately **broad and front-loaded**: ask for real material first, then run a wide
batched interview, then research to fill what people couldn't answer. Read this before Step 1.

## The question bank lives in [questionnaire.md](questionnaire.md) — this file is HOW to collect it

The canonical list of questions is the standalone, fillable **[questionnaire.md](questionnaire.md)**
(groups A–H + run-config R, each question tagged `[REQUIRED]`/`[optional]` and mapped to an arc42
section). This `interview.md` no longer duplicates the list — it defines the *collection procedure*
that feeds those exact questions in. Always drive the interview from questionnaire.md so the live
interview, a pre-filled questionnaire, and a TAD all populate the same fields.

## Rule 0 — A structured brief replaces the interview

If the intake already includes a Spryker **TAD** (Target Architecture Definition) or an equivalently
structured architecture brief, **the interview is skipped** — the document IS the intake, and it is
usually richer than a live interview. Read [tad-mapping.md](tad-mapping.md) and follow its fast-path
instead of the rules below. The rest of this file applies only when no structured document exists,
or (Gated mode only) for targeted follow-ups on gaps a provided doc genuinely leaves open.

## Rule 0b — A filled questionnaire also replaces (or shortens) the interview

Before interviewing, check whether the user already gave answers to
[questionnaire.md](questionnaire.md) — as a filled copy (a path or pasted text), or inline in their
request:

- **Fully filled** (every `[REQUIRED]` answered, including explicit "unknown"/"TBD"): treat it exactly
  like a TAD — **skip the interview**, copy the answers verbatim into `intake.md`, and log
  `INTERVIEW | SKIP (questionnaire pre-filled)`.
- **Partially filled:** parse which questions are answered, and **ask ONLY the still-blank ones** —
  never re-ask something already answered. Map each remaining blank to its group/question ID (A1, C3…)
  so the follow-up batches stay coherent.
- **Not provided:** offer the user the choice in Rule 1b before defaulting to a full interview.

Parsing rule: match answers to question IDs (A1, B2, …). A blank, "?", or omitted line = unanswered.
A line saying "unknown"/"TBD"/"n/a" = ANSWERED (an honest gap), not a question to re-ask.

## Rule 1b — Offer "fill the list yourself" vs "interview me"

When no filled questionnaire and no TAD exist, don't just launch into questions. First offer the user
both paths (a single `AskUserQuestion` is fine):

> "I can either (a) hand you the intake questionnaire to fill in at your own pace and then run with no
> interview, or (b) interview you now in a few batched question sets. Which do you prefer?"

- If they choose **fill-it-yourself**: point them at [questionnaire.md](questionnaire.md) (or paste its
  content), tell them the "Minimum viable answer set" line is enough for a first draft, and **wait**
  for their filled answers before proceeding. Then treat the result per Rule 0b.
- If they choose **interview**: run the batched interview below.
- In a **fully autonomous / no-questions** run, do not offer the choice — use whatever answers/TAD were
  provided and mark the rest as TODOs.

## Rule 1 — Ask for existing documents first, before any structured questions

The single most valuable input is material the user already has. Before the multi-tab questions, ask
plainly for it and read everything they give you:

> "Before I ask anything, can you share whatever already exists — a business brief / RFP, discovery or
> workshop notes, a requirements doc or backlog, existing diagrams (even photos of a whiteboard),
> slide decks, a solution proposal, relevant JIRA/Confluence links, or the contract's scope section?
> Paste the text or give me file paths. The more you drop here, the fewer questions I need to ask and
> the more grounded the document will be."

- Read every provided file. Extract answers to the question bank below **from the documents first**,
  and only ask a question when the docs don't already answer it — this respects the user's time and
  makes the interview feel informed, not robotic.
- If a Confluence/JIRA link is given and the Atlassian MCP is connected, pull it. If not, ask the user
  to paste the relevant text.
- Note in `intake.md` which facts came from which document, so section writers can cite sources.

## Rule 2 — Batch the questions (the "multi-tab" intake)

Use `AskUserQuestion` with up to 4 questions per call, several calls back-to-back, so the user gets a
carefully structured interview in one pass rather than a slow drip. Group questions by theme so each
call reads like a coherent "tab" of the interview. Only ask about themes relevant to the **selected**
sections — don't interview about deployment if section 7 wasn't chosen.

Prefer multi-select and give sensible default options with a "(Recommended)" first option where a
common default exists; the user can always choose "Other" to type free-form. For anything open-ended
(volumes, integration specifics, prose descriptions), ask it as an option like "Let me type the
details" and then collect the free text conversationally, or accept that it becomes a `TODO` the user
fills later.

## The question bank = [questionnaire.md](questionnaire.md) — grouped into themed batches

The questions themselves are the groups **A–J (+ run-config R)** in
[questionnaire.md](questionnaire.md); read it, don't re-type it here. Each question is already tagged
`[REQUIRED]`/`[optional]` and mapped to the arc42 section it feeds, and the bank is built to reach
**decision-grade depth** (see [architecture-depth.md](architecture-depth.md)) — release phasing,
per-phase volumes, components/connectors, multi-store split, document governance, and org/tenant model.

Turn the questionnaire groups into `AskUserQuestion` batches like this:
- **One `AskUserQuestion` call per group** (A, B, C, …) reads like a coherent "tab". Each group maps
  cleanly: A→01, B→01/03/05/12, C→02/03/06/11, D→01+SD, E→02/07/08, F→10, G→06/07/08, H→04/09/11,
  I→01 header, J→03/05/08/11, R→run config.
- **Pull only groups relevant to the SELECTED sections** — don't ask group F (volumes → section 10)
  if section 10 wasn't chosen. Always ask group A, group I (governance), and the run-config R. Skip
  group J entirely for a flat single-tenant B2C shop with no org hierarchy.
- **Group C repeats per external system** — collect it as a small table (one row per system) rather
  than one `AskUserQuestion` per system; C4 (widget-vs-backend classification) and C7 (auth) are the
  required per-system fields.
- **Ask `[REQUIRED]` first**, `[optional]` only if the group has room (≤4 questions per call). If a
  group has more than 4 questions, split across two back-to-back calls rather than dropping any.
- Prefer multi-select with a "(Recommended)" default first option, and an "Other / let me type"
  escape for open-ended answers (volumes, integration specifics). An answer the user can't give
  becomes a `TODO`, not a fabricated value.
- When only SOME questions are unanswered (partial questionnaire, per Rule 0b), batch just those,
  labelling each by its questionnaire ID (A1, C3…).

## After the interview — always write answers to the input artifact

**Every answer collected — however it arrived (live interview, a filled/partly-filled questionnaire,
or extracted from a provided doc/TAD) — is written down to `intake.md` under the run directory.** This
is non-negotiable: `intake.md` is the single shared input every section-writer teammate reads, and the
run's durable record of what was actually said. Rules:
- Organize `intake.md` by the questionnaire group / arc42 section each answer feeds, keyed by question
  ID (A1, B2, …) so nothing is lost and gaps are visible.
- Note the **source** of each fact: `interview`, `questionnaire (pre-filled)`, `<document name>`, or
  `unknown — TODO`. Section writers cite these sources.
- Capture answers as they come in during a live interview (append after each batch), so a long or
  interrupted interview never loses what was already answered.
- If a whole selected section has almost no input, say so to the user (Gated) or record it as a
  TODO-heavy section (Autonomous) — never pad it.
