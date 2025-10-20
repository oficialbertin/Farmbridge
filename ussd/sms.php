<?php
// Try to load Composer autoload from common locations; do not fatal if missing
@include_once __DIR__ . '/vendor/autoload.php';
if (!class_exists('AfricasTalking\\SDK\\AfricasTalking')) {
    @include_once dirname(__DIR__) . '/vendor/autoload.php'; // project root vendor
}

require_once __DIR__ . '/util.php';

class Sms {
    private $sms;
    private $sdkAvailable = false;

    public function __construct() {
        if (class_exists('AfricasTalking\\SDK\\AfricasTalking')) {
            $username = Util::$username; 
            $apiKey = Util::$apikey;
            try {
                $AT = new \AfricasTalking\SDK\AfricasTalking($username, $apiKey);
                $this->sms = $AT->sms();
                $this->sdkAvailable = true;
            } catch (\Throwable $e) {
                error_log('Africa\'s Talking SDK init failed: ' . $e->getMessage());
                $this->sdkAvailable = false;
            }
        } else {
            $this->sdkAvailable = false;
            error_log("AfricasTalking SDK not found. SMS will be logged only. Install via Composer: composer require africastalking/africastalking");
        }
    }

    public function sendRegistrationSMS($recipient, $name, $role) {
        $message = "Welcome to FarmBridge AI, $name! Your registration as a $role was successful. You can now access all farming and trading services.";
        $this->sendSMS($message, $recipient);
    }

    public function sendProductListedSMS($recipient, $farmerName, $productName, $quantity, $price) {
        $message = "Hello $farmerName,\nYour product '$productName' has been successfully listed:\nQuantity: $quantity\nPrice: " . Util::formatPrice($price) . "\nIt's now available for buyers.";
        $this->sendSMS($message, $recipient);
    }

    public function sendOrderConfirmationSMS($recipient, $buyerName, $productName, $farmerName, $quantity, $totalPrice) {
        $message = "Hello $buyerName,\nYour order has been confirmed:\nProduct: $productName\nFarmer: $farmerName\nQuantity: $quantity\nTotal: " . Util::formatPrice($totalPrice) . "\nContact the farmer for delivery details.";
        $this->sendSMS($message, $recipient);
    }

    public function sendOrderNotificationToFarmerSMS($recipient, $farmerName, $productName, $buyerName, $quantity, $totalPrice) {
        $message = "Hello $farmerName,\nYou have a new order:\nProduct: $productName\nBuyer: $buyerName\nQuantity: $quantity\nTotal: " . Util::formatPrice($totalPrice) . "\nPlease contact the buyer for delivery.";
        $this->sendSMS($message, $recipient);
    }

    public function sendMarketPriceSMS($recipient, $cropName, $price, $location) {
        $message = "Market Price Update:\nCrop: $cropName\nPrice: " . Util::formatPrice($price) . "\nLocation: $location\nUpdated: " . date('Y-m-d H:i:s');
        $this->sendSMS($message, $recipient);
    }

    public function sendFarmingTipSMS($recipient, $tipTitle, $tipContent) {
        $message = "Farming Tip: $tipTitle\n\n$tipContent\n\n- FarmBridge AI";
        $this->sendSMS($message, $recipient);
    }

    public function sendPaymentConfirmationSMS($recipient, $orderId, $amount) {
        $message = "Payment Confirmation:\nOrder ID: $orderId\nAmount: " . Util::formatPrice($amount) . "\nStatus: Completed\nThank you for using FarmBridge AI!";
        $this->sendSMS($message, $recipient);
    }

    public function sendDisputeNotificationSMS($recipient, $disputeId, $issue) {
        $message = "Dispute Notification:\nDispute ID: $disputeId\nIssue: $issue\nOur team will review and resolve this matter soon.";
        $this->sendSMS($message, $recipient);
    }

    public function sendWelcomeBackSMS($recipient, $name) {
        $message = "Welcome back to FarmBridge AI, $name! Access farming tips, market prices, and trading services anytime.";
        $this->sendSMS($message, $recipient);
    }

    public function sendAccountUpdateSMS($recipient, $name, $updateType) {
        $message = "Hello $name,\nYour $updateType has been successfully updated in FarmBridge AI.";
        $this->sendSMS($message, $recipient);
    }

    public function sendLowStockAlertSMS($recipient, $farmerName, $productName, $remainingQuantity) {
        $message = "Low Stock Alert:\nHello $farmerName,\nYour product '$productName' is running low.\nRemaining quantity: $remainingQuantity\nConsider updating your listing.";
        $this->sendSMS($message, $recipient);
    }

    public function sendSeasonalReminderSMS($recipient, $farmerName, $season, $tips) {
        $message = "Seasonal Reminder:\nHello $farmerName,\n$season season is approaching.\nTips: $tips\nPlan your farming activities accordingly.";
        $this->sendSMS($message, $recipient);
    }

    public function sendSystemMaintenanceSMS($recipient, $maintenanceTime) {
        $message = "System Maintenance Notice:\nFarmBridge AI will undergo maintenance on $maintenanceTime.\nServices may be temporarily unavailable.\nWe apologize for any inconvenience.";
        $this->sendSMS($message, $recipient);
    }

    public function sendPromotionalSMS($recipient, $promotion) {
        $message = "Special Offer:\n$promotion\n\nDon't miss out on this opportunity!\n- FarmBridge AI";
        $this->sendSMS($message, $recipient);
    }

    public function sendEmergencyAlertSMS($recipient, $alertType, $message) {
        $emergencyMessage = "EMERGENCY ALERT - $alertType:\n$message\n\nStay safe and follow local authorities' instructions.\n- FarmBridge AI";
        $this->sendSMS($emergencyMessage, $recipient);
    }

    public function sendWeatherAlertSMS($recipient, $location, $weatherCondition, $recommendation) {
        $message = "Weather Alert for $location:\nCondition: $weatherCondition\nRecommendation: $recommendation\n\nPlan your farming activities accordingly.\n- FarmBridge AI";
        $this->sendSMS($message, $recipient);
    }

    public function sendBulkSMS($recipients, $message) {
        $this->sendSMS($message, $recipients);
    }

    private function sendSMS($message, $recipients) {
        $from = Util::$Company;

        // If SDK missing, log instead of crashing
        if (!$this->sdkAvailable) {
            error_log("[SMS-DRYRUN] to=" . (is_array($recipients) ? implode(', ', $recipients) : $recipients) . ", from={$from}, msg=" . substr($message, 0, 120));
            return false;
        }

        try {
            $result = $this->sms->send([
                'to'      => $recipients,
                'message' => $message,
                'from'    => $from
            ]);
            
            // Log SMS sending for debugging
            error_log("SMS sent to " . (is_array($recipients) ? implode(', ', $recipients) : $recipients) . ": " . substr($message, 0, 50) . "...");
            
            return $result;
        } catch (\Throwable $e) {
            error_log("SMS Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper method to send SMS in user's preferred language
    public function sendLocalizedSMS($recipient, $messageKey, $params = [], $language = 'en') {
        $messages = [
            'en' => [
                'registration_success' => "Welcome to FarmBridge AI, {name}! Your registration as a {role} was successful.",
                'product_listed' => "Your product '{product}' has been successfully listed. Quantity: {quantity}, Price: {price}",
                'order_received' => "You have a new order for '{product}' from {buyer}. Quantity: {quantity}, Total: {total}",
                'order_confirmed' => "Your order for '{product}' has been confirmed. Farmer: {farmer}, Total: {total}",
                'market_price' => "Market Price: {crop} - {price} at {location}",
                'farming_tip' => "Farming Tip: {title}\n{content}",
                'payment_success' => "Payment successful! Order: {order_id}, Amount: {amount}",
                'dispute_created' => "Dispute created successfully. ID: {dispute_id}. We'll review it soon."
            ],
            'rw' => [
                'registration_success' => "Murakaza neza muri FarmBridge AI, {name}! Kwiyandikisha kwawe nk'umurimi/umuguzi byagenze neza.",
                'product_listed' => "Igicuruzwa cyawe '{product}' cyanditswe neza. Umubare: {quantity}, Igiciro: {price}",
                'order_received' => "Mufite umugambi mushya wa '{product}' uva kuri {buyer}. Umubare: {quantity}, Umubare wose: {total}",
                'order_confirmed' => "Umugambi wawe wa '{product}' wemejwe. Umurimi: {farmer}, Umubare wose: {total}",
                'market_price' => "Igiciro cy'isoko: {crop} - {price} mu {location}",
                'farming_tip' => "Inama y'ubuhinzi: {title}\n{content}",
                'payment_success' => "Kwishyura byagenze neza! Umugambi: {order_id}, Umubare: {amount}",
                'dispute_created' => "Amakimbirane yaremwe neza. ID: {dispute_id}. Tuzayisuzuma vuba."
            ]
        ];

        if (isset($messages[$language][$messageKey])) {
            $message = $messages[$language][$messageKey];
            
            // Replace placeholders with actual values
            foreach ($params as $key => $value) {
                $message = str_replace('{' . $key . '}', $value, $message);
            }
            
            $this->sendSMS($message, $recipient);
        } else {
            // Fallback to English
            $this->sendLocalizedSMS($recipient, $messageKey, $params, 'en');
        }
    }
}
?>
