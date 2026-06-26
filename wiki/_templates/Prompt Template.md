---
type: prompt
project: BreadLab
created: <% tp.date.now("YYYY-MM-DD") %>
tags:
  - breadlab
  - codex
  - prompt
---

# Prompt - <% tp.file.title %>

## Context Files
Baca dulu:
- `wiki/00-Project-Overview.md`
- `wiki/18-Coding-Rules.md`

## Task
Tugas:

## Boundaries
Jangan ubah:
- POS finalize
- checkout
- payment
- shipment
- finance

## Expected Output
Setelah selesai, jelaskan:
- ringkasan perubahan
- files changed
- routes changed
- migration changed
- cara test
- risiko

## Prompt

```text
Baca dulu:
- wiki/00-Project-Overview.md
- wiki/18-Coding-Rules.md

Tugas:


Batasan:
- Jangan ubah modul yang tidak diminta.
- Jangan buat migration kecuali perlu.
- Jangan ubah checkout, payment, shipment, finance, atau POS finalize kecuali diminta.

Setelah selesai, jelaskan:
- ringkasan perubahan
- files changed
- routes changed
- migration changed
- cara test
- risiko
- update wiki yang dilakukan