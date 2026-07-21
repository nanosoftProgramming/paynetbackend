## 5. Test the endpoints

**Register**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
    "username": "maged_dev",
    "email": "maged@example.com",
    "organization_name": "Maged Studio",
    "password": "Passw0rd123",
    "password_confirmation": "Passw0rd123"
  }'
```

**Login**
```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email": "maged@example.com", "password": "Passw0rd123", "device_name": "flutter-app"}'
```

**Authenticated request** (use the `token` from login/register)
```bash
curl http://127.0.0.1:8000/api/v1/auth/me \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"
```