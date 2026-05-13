---
name: forms-system
description: >
  Implement and operate the Termosalud dynamic forms module in this DDD/CQRS multitenant API.
  Use when creating forms, validating field schemas, handling submissions, anti-spam checks,
  and asynchronous CRM forwarding.
argument-hint: 'Describe the form or flow (e.g. "Create contact-main form and enable public submit")'
---

# Forms System Agent (Termosalud 2026)

This skill defines the dynamic forms module behavior and implementation rules.

## Data Model

### Form Definition (`forms`)
- `key`: Unique technical identifier (slug-like). Used for API submissions.
- `name`: Internal admin name.
- `recipient_email`: Optional destination email for notifications.
- `fields`: JSON array of field objects.
- `active`: Boolean on/off flag.

### Form Submissions (`form_submissions`)
- `form_id`: FK-like reference to the form definition.
- `data`: JSON object of answers (`field.name` => user value).
- `ip_address` and `user_agent`: metadata for anti-spam and traceability.

## Field Schema

Each field item inside `fields` must follow:

```json
{
  "name": "field_name",
  "label": "Visible Label",
  "type": "field_type",
  "required": true
}
```

Supported `type` values:
- `text`
- `email`
- `tel`
- `textarea`
- `select`
- `checkbox`

## API Workflow

### Phase 1: Create Form
- Endpoint: `POST /{tenant}/{version}/forms`
- Typical payload:

```json
{
  "name": "Contact Main",
  "key": "contact-main",
  "recipient_email": "leads@termosalud.com",
  "active": true,
  "fields": [
    { "name": "full_name", "label": "Nombre Completo", "type": "text", "required": true },
    { "name": "email", "label": "Correo Electrónico", "type": "email", "required": true },
    { "name": "message", "label": "Mensaje", "type": "textarea", "required": false }
  ]
}
```

### Phase 2: Embed in Block Editor
Use a `form` block:

```json
{
  "type": "form",
  "data": {
    "form_id": 1
  }
}
```

### Phase 3: Submit
- Endpoint: `POST /{tenant}/{version}/forms/{key}/submit`
- Includes anti-spam checks and async processing.

## Anti-Spam Rules

- Honeypot hidden input (must be empty).
- IP + form throttle window.
- reCAPTCHA v3 can be plugged into the same protection service.

## Async Processing

- Submission is stored first.
- Then enqueue `SendToCrmJob` for CRM forwarding.
- Job should be idempotent and log failures without breaking API response.

## Best Practices

1. Use stable, semantic field names (`email`, `phone`, `full_name`).
2. Keep forms short for conversion.
3. Mark primary contact fields as required.
4. Keep `key` immutable after creation.
