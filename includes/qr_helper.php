<?php
/**
 * QR Code Generator Helper
 * ใช้ API ฟรีจาก api.qrserver.com
 */

class QRCodeGenerator {
    
    /**
     * สร้าง QR Code PromptPay
     * @param string $promptpay_id เลขบัญชี PromptPay (เบอร์โทร/เลขบัตรประชาชน)
     * @param float $amount จำนวนเงิน
     * @return string URL ของ QR Code
     */
    public static function generatePromptPayQR($promptpay_id, $amount = null) {
        // แปลง PromptPay ID
        $id = str_replace('-', '', $promptpay_id);
        
        // สร้าง Payload ตาม PromptPay Standard
        $payload = self::buildPromptPayPayload($id, $amount);
        
        // สร้าง QR Code
        $size = '400x400';
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($payload);
        
        return $qr_url;
    }
    
    /**
     * สร้าง QR Code สำหรับข้อมูลสินค้า
     * @param string $product_url URL ของหน้าสินค้า
     * @return string URL ของ QR Code
     */
    public static function generateProductQR($product_url) {
        $size = '300x300';
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($product_url);
        return $qr_url;
    }
    
    /**
     * สร้าง QR Code สำหรับ Tracking
     * @param string $tracking_number เลข Tracking
     * @param string $courier ผู้ให้บริการ (thai_post, kerry, flash)
     * @return string URL ของ QR Code
     */
    public static function generateTrackingQR($tracking_number, $courier = 'thai_post') {
        $tracking_urls = [
            'thai_post' => "https://track.thailandpost.co.th/?trackNumber={$tracking_number}",
            'kerry' => "https://th.kerryexpress.com/th/track/?track={$tracking_number}",
            'flash' => "https://www.flashexpress.co.th/tracking/?se={$tracking_number}",
        ];
        
        $url = $tracking_urls[$courier] ?? $tracking_urls['thai_post'];
        
        $size = '300x300';
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($url);
        return $qr_url;
    }
    
    /**
     * สร้าง QR Code ทั่วไป
     * @param string $data ข้อมูลที่ต้องการใส่ใน QR
     * @param string $size ขนาด QR (เช่น 300x300)
     * @return string URL ของ QR Code
     */
    public static function generateQR($data, $size = '300x300') {
        $qr_url = "https://api.qrserver.com/v1/create-qr-code/?size={$size}&data=" . urlencode($data);
        return $qr_url;
    }
    
    /**
     * สร้าง Payload สำหรับ PromptPay (ตาม EMV Standard)
     * @param string $id PromptPay ID
     * @param float $amount จำนวนเงิน
     * @return string Payload
     */
    private static function buildPromptPayPayload($id, $amount = null) {
        // ใช้รูปแบบง่ายๆ ที่ App PromptPay สามารถอ่านได้
        // Format: PromptPay ID|Amount
        
        if ($amount !== null) {
            // มีจำนวนเงิน
            return "PromptPay:{$id}:" . number_format($amount, 2, '.', '');
        } else {
            // ไม่มีจำนวนเงิน (ให้ผู้ใช้กรอกเอง)
            return "PromptPay:{$id}";
        }
    }
}

/**
 * ตัวอย่างการใช้งาน:
 * 
 * // 1. PromptPay QR
 * $qr = QRCodeGenerator::generatePromptPayQR('0812345678', 599.00);
 * 
 * // 2. Product QR
 * $qr = QRCodeGenerator::generateProductQR('https://myshop.com/product/123');
 * 
 * // 3. Tracking QR
 * $qr = QRCodeGenerator::generateTrackingQR('TH123456789', 'thai_post');
 * 
 * // 4. Generic QR
 * $qr = QRCodeGenerator::generateQR('Any text or URL');
 */
?>