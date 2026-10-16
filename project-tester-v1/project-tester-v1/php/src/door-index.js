const API_URL = "https://marco-warm-because-nancy.trycloudflare.com/api/door-authenticate.php";

const form = document.getElementById("doorForm");
const roomInput = document.getElementById("room");
const codeInput = document.getElementById("code");
const submitButton = document.getElementById("submitButton");
const statusBox = document.getElementById("status");

function showStatus(message, type) {
  statusBox.textContent = message;
  statusBox.className = `status ${type}`;
  statusBox.hidden = false;
}

form.addEventListener("submit", async (event) => {
  event.preventDefault();
  const room = roomInput.value;
  const code = codeInput.value.trim();

  if (!room || !/^\d{6}$/.test(code)) {
    showStatus("กรุณาเลือกห้องและกรอกรหัส 6 หลัก", "error");
    return;
  }

  submitButton.disabled = true;
  submitButton.textContent = "กำลังตรวจสอบ...";
  statusBox.hidden = true;

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ room, code })
    });
    const result = await response.json();

    if (!response.ok || result.open !== true) {
      throw new Error(result.message || "รหัสไม่ถูกต้อง");
    }

    showStatus(`ยืนยันสำเร็จ ห้อง ${result.room} สามารถเปิดประตูได้`, "success");
    codeInput.value = "";
  } catch (error) {
    showStatus(error.message || "เชื่อมต่อ API ไม่สำเร็จ กรุณาลองใหม่", "error");
  } finally {
    submitButton.disabled = false;
    submitButton.textContent = "ยืนยันเพื่อเปิดประตู";
  }
});