---
name: cv-tuning
description: "Use this skill when retuning a CV against a job ad. Triggers whenever a CV document exported from the portfolio admin is pasted in — recognisable by a top-level \"cv\" key, or by top-level \"id\", \"summary\", \"stack_highlights\", \"skills\" and \"experience\" keys — or when asked to tailor, retarget, rewrite or shorten a CV for a particular role or job description. Covers what may be changed, what must be copied back untouched, the character limits, the questions to ask before writing, and the format the document has to come back in — including the job_application record that goes back with it."
---

# CV tuning

Rewrite an exported CV so it reads as a strong match for one job ad, without
saying anything that is not true — and hand back a record of the job it was
tuned for.

The document is JSON, exported from the portfolio admin and pasted back into it
afterwards. `cv.openapi.yaml`, beside this file, is the authoritative contract:
read it when a field's exact constraint matters. This file is the working
summary.

## The one rule that matters most

**Nothing may be invented.** Not an employer, a job title, a qualification, a
project, a technology, or a date. Every fact in the retuned CV must already
appear somewhere in the document handed over — or have been supplied by the
person whose CV it is, in this conversation, when you asked.

What you are changing is *emphasis*: which true things are said, in what order,
in what words, and which are left out to make room. That is the whole job.

The job ad half is different, and looser: it records what the ad asked for, not
what the applicant claims. Quote it freely. Leave a field null when the ad does
not say — never infer a seniority from a years figure, or a years figure from a
seniority.

## Before you write, ask

The export is everything the admin knows, which is less than the person does.
Three questions are usually worth asking, in one message, before writing
anything:

1. **"The ad asks for X, Y and Z, which aren't on your CV. Do you have any of
   them?"** Name them from the ad — that is the list they will not think of
   unprompted. Anything they confirm is now a fact and may go in. Anything they
   do not confirm stays out, however much the ad wants it.
2. **"Where did you apply?"** — the company, and the channel: LinkedIn,
   profession.hu, email, the company's own site, or some other HR system by
   name. This fills `job_application`, and without it the record is a job with
   no address.
3. Anything the ad is silent on that matters — remote or on-site, the seniority,
   a salary figure mentioned in passing. Ask once; do not interrogate.

If the ad is pasted in with no other context, ask before writing. If the person
has already answered, do not ask again.

## What may change, and what may not

| | Fields | What you may do |
| --- | --- | --- |
| **Fixed** | `id`, `locale`, `full_name`, `avatar_path`, `email`, `phone`, `location`, `linkedin_url`, `github_url`, `portfolio_url`, `languages` | Nothing. Copy back byte for byte. They are there so you know who the CV is for. |
| **Facts** | `company`, `title`, `period`, `location` in `experience`; every field in `education`; `title` in `projects` | Reorder entries. Drop entries. Never reword one, and never add one. |
| **Prose** | `label`, `role`, `summary`, `stack_highlights`, `skills`, `bullets`, `stack` | Rewrite freely, within the limits below and the no-invention rule. |

`id` is how the admin checks the document belongs to the CV being edited. Change
it and the import is refused; drop it and the import is refused.

## Limits

Every text field on the CV is capped at **500 characters**, and `summary` counts
its HTML markup toward that. The result is printed to **one A4 page** — the
limits are ceilings, not targets, and a CV that fills every one of them will not
fit.

`summary` is rich text. Only `<p>`, `<strong>`, `<em>` and `<a>` survive; any
other tag is stripped by the editor.

## How to work

1. **Read the job ad first**, then the CV. Note what the ad asks for repeatedly
   or lists first — that is what it actually cares about.
2. **Ask the questions above**, and wait.
3. **`label`** — name it after the job ad, e.g. "Acme — Senior Backend". It is
   never printed; it is how the CV is found again in the admin.
4. **`role`** — match the ad's own wording where it honestly describes the same
   work. "Senior Backend Engineer" over "Full-stack developer" if that is what
   the ad calls the job and the work fits.
5. **`summary`** — two lines. Lead with the thing the ad most wants. Present
   tense, specific, no "passionate problem-solver" filler.
6. **`stack_highlights`** — five or six, ordered by what the ad asks for. Only
   technologies that appear elsewhere in the document.
7. **`skills`** — within each group, relevant first, and drop what does not earn
   its space on a single page.
8. **`experience`** — keep the entries in reverse chronological order; that is
   what a reader expects. Rewrite `bullets`: one idea per line, a concrete
   outcome over a duty, three to five lines for a recent role and fewer further
   back. Drop bullets that say nothing about this job.
9. **`projects`** — select and order for the ad. Reorder each `stack` so what
   the ad asks for is visible first.
10. **`education`** — usually untouched. Drop an entry only if the page is full.
11. **`job_application`** — fill it in from the ad and the answers, below.
12. **Re-read against the ad.** If a line would not make a reader more likely to
    interview this person for *this* job, cut it.

## The job application half

One record of where this CV went. `company` is the only field that must be
filled in; everything else is null unless the ad or the person actually said it.

| Field | What goes in it |
| --- | --- |
| `company` | Who it went to. Ask if the ad does not name them. |
| `title` | The advertised job title, exactly as the ad writes it. |
| `method` | `linkedin`, `profession_hu`, `email`, `company_site`, `ats`, `other`. |
| `method_detail` | Which system, when `method` is `ats` or `other` — "Greenhouse", "Teamtailor". |
| `source_url` | Link to the posting. |
| `location` | As the ad puts it, including "Remote" or "Hybrid, Budapest". |
| `experience_level` | `junior`, `medior`, `senior`, `lead` — only if the ad names one. |
| `required_years` | Overall years asked for. A range takes its lower bound. |
| `required_skills` | `[{ "name": "Laravel", "years": 3 }, …]`, in the order the ad stresses them. |
| `job_ad` | The ad itself, **verbatim**. Do not summarise it. |
| `notes` | A referral, a salary figure in passing, which parts of the CV were stretched thinnest. |
| `applied_at` | `YYYY-MM-DD`. Defaults to today. |

`required_skills` is the one place to write down what the CV *cannot* answer.
Include the gaps — that is the useful part of the record, and nobody is claiming
them.

`id` appears in `job_application` only when the CV already has an application on
record. Copy it back and that record is updated; leave it out and a new one is
created.

## Handing it back

Return **one JSON object with both halves**:

```json
{
  "cv": { "id": 12, "role": "…", "summary": "…", … },
  "job_application": { "company": "Acme", "method": "linkedin", … }
}
```

The `cv` half is **the complete document**, not a fragment and not a diff —
every field that came in, including the fixed ones.

Omit `job_application` entirely when no particular job is in play — a general
polish rather than an application.

The importer forgives a ```json fence, a sentence either side, and a trailing
comma. It refuses a renamed field, so use the exact key names from the document
you were given. Importing **saves** the CV and records the application as
pending, so hand back only what you would be happy to see live.

The one exception to returning everything: if the request is narrow — "just redo
the summary" — a bare CV object containing `id` plus only the fields you changed
is valid, with no envelope. Anything left out keeps its current value. When in
doubt, return everything.

## What not to do

- Do not translate the CV. Write in the language given by `locale`.
- Do not add a skill, a certification, or a technology because the ad asks for
  it. Ask; and if the answer is no, it is not there.
- Do not inflate a title. `title` is a matter of record.
- Do not pad to the character limit. Shorter is usually better on one page.
- Do not summarise the job ad into `job_ad`. It is a snapshot, not a précis.
- Do not return prose about what you changed unless asked; return the document.
