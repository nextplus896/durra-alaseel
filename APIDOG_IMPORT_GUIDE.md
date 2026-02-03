# Apidog Import Guide (Dorra Alaseel API)

This repo can generate an OpenAPI 3 spec from the included Postman collections, ready to import into Apidog.

## 1) Generate OpenAPI

From the project root:

```bash
npm install
npm run generate:openapi
```

This generates:

- `openapi/openapi.yaml` (merged; recommended for Apidog import)
- `openapi/dorra-alaseel-api-v1.openapi.yaml` (main API v1 only)
- `openapi/twilio-otp.openapi.yaml` (OTP endpoints only)

## 2) Import into Apidog

1. Open Apidog.
2. Create or open a project.
3. Import → **OpenAPI/Swagger**.
4. Select one of the generated YAML files (typically `openapi/openapi.yaml`).
5. When prompted for the server/base URL:
    - Replace `{{base_url}}` with your actual base URL, e.g. `http://192.168.1.211:8001`.

## 3) Auth setup in Apidog

The Postman collections typically use an Authorization header like:

- `Authorization: Bearer <token>`

In Apidog you can configure this as a global header or an auth scheme at the project/environment level.

## Notes

- The spec is generated from Postman request definitions; request/response examples are included when present in the collections.
- If you update the Postman collections, rerun `npm run generate:openapi` to refresh the spec.
