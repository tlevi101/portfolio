---
name: cv-tuning
description: "Use this skill when retuning a CV against a job ad. Triggers whenever a CV JSON document exported from the portfolio admin is pasted in — recognisable by its top-level \"id\", \"summary\", \"stack_highlights\", \"skills\" and \"experience\" keys — or when asked to tailor, retarget, rewrite or shorten a CV for a particular role or job description. Covers what may be changed, what must be copied back untouched, the character limits, and the format the document has to come back in."
---

# CV tuning

Rewrite an exported CV so it reads as a strong match for one job ad, without
saying anything that is not already true.

The document is JSON, exported from the portfolio admin and pasted back into it
afterwards. `cv.openapi.yaml`, beside this file, is the authoritative contract:
read it when a field's exact constraint matters. This file is the working
summary.

## The one rule that matters most

**Nothing may be invented.** Not an employer, a job title, a qualification, a
project, a technology, or a date. Every fact in the retuned CV must already
appear somewhere in the document handed over.

What is being changed is *emphasis*: which true things are said, in what order,
in what words, and which are left out to make room. That is the whole job.

## What may change, and what may not

| | Fields | What you may do |
| --- | --- | --- |
| **Fixed** | `id`, `locale`, `full_name`, `avatar_path`, `email`, `phone`, `location`, `linkedin_url`, `github_url`, `portfolio_url`, `languages` | Nothing. Copy back byte for byte. They are there so you know who the CV is for. |
| **Facts** | `company`, `title`, `period`, `location` in `experience`; every field in `education`; `title` in `projects` | Reorder entries. Drop entries. Never reword one, and never add one. |
| **Prose** | `label`, `role`, `summary`, `stack_highlights`, `skills`, `bullets`, `stack` | Rewrite freely, within the limits below and the no-invention rule. |

`id` is how the admin checks the document belongs to the CV being edited. Change
it and the import is refused; drop it and the import is refused.

## Limits

Every text field is capped at **500 characters**, and `summary` counts its HTML
markup toward that. The result is printed to **one A4 page** — the limits are
ceilings, not targets, and a CV that fills every one of them will not fit.

`summary` is rich text. Only `<p>`, `<strong>`, `<em>` and `<a>` survive; any
other tag is stripped by the editor.

## How to work

1. **Read the job ad first**, then the CV. Note what the ad asks for repeatedly
   or lists first — that is what it actually cares about.
2. **`role`** — match the ad's own wording where it honestly describes the same
   work. "Senior Backend Engineer" over "Full-stack developer" if that is what
   the ad calls the job and the work fits.
3. **`summary`** — two lines. Lead with the thing the ad most wants. Present
   tense, specific, no "passionate problem-solver" filler.
4. **`stack_highlights`** — five or six, ordered by what the ad asks for. Only
   technologies that appear elsewhere in the document.
5. **`skills`** — within each group, relevant first, and drop what does not earn
   its space on a single page. Never add one that was not already listed.
6. **`experience`** — keep the entries in reverse chronological order; that is
   what a reader expects. Rewrite `bullets`: one idea per line, a concrete
   outcome over a duty, three to five lines for a recent role and fewer further
   back. Drop bullets that say nothing about this job.
7. **`projects`** — select and order for the ad. Reorder each `stack` so what
   the ad asks for is visible first.
8. **`education`** — usually untouched. Drop an entry only if the page is full.
9. **Re-read against the ad.** If a line would not make a reader more likely to
   interview this person for *this* job, cut it.

## Handing it back

Return **the complete document**, not a fragment and not a diff — every field
that came in, including the fixed ones, as one JSON object.

The importer forgives a ```json fence, a sentence either side, and a trailing
comma. It refuses a renamed field, so use the exact key names from the document
you were given.

The one exception to returning everything: if the request is narrow — "just
redo the summary" — a document containing `id` plus only the fields you changed
is valid. Anything left out keeps its current value. When in doubt, return
everything.

## What not to do

- Do not translate the CV. Write in the language given by `locale`.
- Do not add a "Skills" entry, a certification, or a technology because the ad
  asks for it. If it is not in the document, the answer is that it is not there.
- Do not inflate a title. `title` is a matter of record.
- Do not pad to the character limit. Shorter is usually better on one page.
- Do not return prose about what you changed unless asked; return the document.
