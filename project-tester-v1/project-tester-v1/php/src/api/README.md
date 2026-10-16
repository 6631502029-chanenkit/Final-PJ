# OTP API

มี endpoint สำหรับเว็บปลายทาง 2 จุด:

## 1. ขอรหัส 6 หลัก

`POST /api/request-code.php`

Request JSON:

```json
{
  "identifier": "user@example.com",
  "purpose": "login"
}
```

Response:

```json
{
  "ok": true,
  "request_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "expires_at": "2026-09-30 12:34:56",
  "message": "สร้างรหัสแล้ว กรุณาตรวจสอบช่องทางปลายทางของคุณ"
}
```

`request_id` ต้องเก็บไว้ส่งไปตรวจสอบพร้อมรหัสในขั้นตอนถัดไป รหัสมีอายุ 10 นาที และขอใหม่ได้ทุก 60 วินาทีต่อ identifier/purpose

## 2. ตรวจรหัส

`POST /api/verify-code.php`

Request JSON:

```json
{
  "request_id": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "code": "123456"
}
```

Response สำเร็จ:

```json
{
  "ok": true,
  "message": "ยืนยันรหัสสำเร็จ"
}
```

รหัสผิดจะถูกจำกัดไว้ 5 ครั้ง และรหัสที่ผ่านแล้วใช้ซ้ำไม่ได้

## 3. ยืนยันรหัสเพื่อเปิดประตู

`POST /api/door-authenticate.php`

Request JSON:

```json
{
  "room": "101",
  "code": "123456"
}
```

Response สำเร็จ:

```json
{
  "ok": true,
  "open": true,
  "room": "101",
  "message": "ยืนยันสำเร็จ สามารถเปิดประตูได้"
}
```

Endpoint นี้ตรวจรหัสหลักหรือรหัสชั่วคราวของการจองที่ active และอยู่ในช่วงเวลาเข้าพักเท่านั้น หน้าอุปกรณ์ตัวอย่างอยู่ที่ `/door-index.html` และสามารถตั้งค่า URL API ใน `door-index.js` ได้

## การส่งรหัสไปปลายทาง

ตั้งค่า environment variable `OTP_WEBHOOK_URL` ให้เป็น HTTPS endpoint ของระบบอีเมล/SMS/เว็บปลายทาง โดย API นี้จะส่ง JSON ต่อไปนี้ด้วย `POST`:

```json
{
  "identifier": "user@example.com",
  "purpose": "login",
  "code": "123456"
}
```

ถ้าไม่ตั้งค่า webhook ระบบจะสร้างและเก็บรหัสได้ แต่จะยังไม่มีผู้ให้บริการส่งจริง ใน local development สามารถตั้ง `OTP_DEBUG=true` เพื่อให้ response มี `debug_code` ชั่วคราว ห้ามเปิดค่านี้บน production

ตาราง `otp_requests` จะถูกสร้างอัตโนมัติครั้งแรกที่เรียก API และเก็บเฉพาะ hash ของรหัส ไม่เก็บรหัสจริง
