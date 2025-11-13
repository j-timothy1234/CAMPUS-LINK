# Rider API Examples

Register (multipart/form-data):

```bash
curl -i -X POST http://localhost/CAMPUS-LINK/rider_api/register.php \
  -F "username=johndoe" \
  -F "email=johndoe@gmail.com" \
  -F "phone=0712345678" \
  -F "gender=Male" \
  -F "plate=UAA 123A" \
  -F "residence=Kampala" \
  -F "password=Secret123!" \
  -F "photo=@/path/to/photo.jpg"
```

Check unique (plate):

```bash
curl -i "http://localhost/CAMPUS-LINK/rider_api/check_unique.php?plate=UAA%20123A"
```

Notes: endpoint will create PHP session and return JSON with `redirect` on success.
