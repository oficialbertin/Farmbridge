<?php
/**
 * FarmBridge AI USSD Application Test Script
 * This script helps test the USSD functionality locally
 */

require_once 'util.php';
require_once 'database.php';
require_once 'menu.php';

class USSDTester {
    private $db;
    private $testSessionId;
    private $testPhoneNumber;

    public function __construct() {
        $this->db = new Database();
        $this->testSessionId = 'test_session_' . uniqid();
        $this->testPhoneNumber = '250788123456';
    }

    public function runTests() {
        echo "🌾 FarmBridge AI USSD Application Test Suite\n";
        echo "==========================================\n\n";

        $this->testDatabaseConnection();
        $this->testUserRegistration();
        $this->testProductListing();
        $this->testMarketPrices();
        $this->testFarmingTips();
        $this->testUSSDMenuFlow();

        echo "\n✅ All tests completed!\n";
    }

    private function testDatabaseConnection() {
        echo "🔌 Testing Database Connection...\n";
        try {
            $connection = $this->db->getConnection();
            if ($connection) {
                echo "✅ Database connection successful\n";
            } else {
                echo "❌ Database connection failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Database connection error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testUserRegistration() {
        echo "👤 Testing User Registration...\n";
        try {
            // Test phone number validation
            $validPhone = Util::isValidPhoneNumber('250788123456');
            echo $validPhone ? "✅ Phone number validation works\n" : "❌ Phone number validation failed\n";

            // Test email validation
            $validEmail = Util::isValidEmail('test@example.com');
            echo $validEmail ? "✅ Email validation works\n" : "❌ Email validation failed\n";

            // Test user registration
            $userId = $this->db->registerUser(
                $this->testPhoneNumber,
                'Test User',
                'test@example.com',
                'farmer',
                'KIGALI',
                'Gasabo',
                'Test Sector'
            );

            if ($userId) {
                echo "✅ User registration successful (ID: $userId)\n";
                
                // Test user retrieval
                $user = $this->db->getUserByPhone($this->testPhoneNumber);
                if ($user) {
                    echo "✅ User retrieval successful\n";
                } else {
                    echo "❌ User retrieval failed\n";
                }
            } else {
                echo "❌ User registration failed\n";
            }
        } catch (Exception $e) {
            echo "❌ User registration error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testProductListing() {
        echo "🌱 Testing Product Listing...\n";
        try {
            // Get test user
            $user = $this->db->getUserByPhone($this->testPhoneNumber);
            if (!$user) {
                echo "❌ No test user found for product testing\n";
                return;
            }

            // Test product addition
            $productId = $this->db->addProduct(
                $user['id'],
                'Test Maize',
                'High quality maize for testing',
                100,
                500,
                'Cereals'
            );

            if ($productId) {
                echo "✅ Product listing successful (ID: $productId)\n";
                
                // Test product retrieval
                $product = $this->db->getProductById($productId);
                if ($product) {
                    echo "✅ Product retrieval successful\n";
                } else {
                    echo "❌ Product retrieval failed\n";
                }

                // Test product search
                $products = $this->db->searchProducts('maize', 10);
                if ($products && count($products) > 0) {
                    echo "✅ Product search successful\n";
                } else {
                    echo "❌ Product search failed\n";
                }
            } else {
                echo "❌ Product listing failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Product listing error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testMarketPrices() {
        echo "💰 Testing Market Prices...\n";
        try {
            // Test market price addition
            $priceId = $this->db->addMarketPrice('Maize', 450, 'Kigali', 'kg');
            if ($priceId) {
                echo "✅ Market price addition successful (ID: $priceId)\n";
                
                // Test market price retrieval
                $prices = $this->db->getMarketPrices('Maize', 10);
                if ($prices && count($prices) > 0) {
                    echo "✅ Market price retrieval successful\n";
                } else {
                    echo "❌ Market price retrieval failed\n";
                }
            } else {
                echo "❌ Market price addition failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Market prices error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testFarmingTips() {
        echo "🌾 Testing Farming Tips...\n";
        try {
            // Test farming tip addition
            $tipId = $this->db->addFarmingTip(
                'Test Farming Tip',
                'This is a test farming tip for maize cultivation.',
                'SEASONAL'
            );

            if ($tipId) {
                echo "✅ Farming tip addition successful (ID: $tipId)\n";
                
                // Test farming tip retrieval
                $tips = $this->db->getFarmingTips('SEASONAL', 10);
                if ($tips && count($tips) > 0) {
                    echo "✅ Farming tip retrieval successful\n";
                } else {
                    echo "❌ Farming tip retrieval failed\n";
                }
            } else {
                echo "❌ Farming tip addition failed\n";
            }
        } catch (Exception $e) {
            echo "❌ Farming tips error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testUSSDMenuFlow() {
        echo "📱 Testing USSD Menu Flow...\n";
        try {
            // Test welcome menu
            $menu = new Menu($this->testSessionId, $this->testPhoneNumber, '');
            $response = $menu->processMenu();
            
            if (strpos($response, 'CON') === 0) {
                echo "✅ Welcome menu works\n";
            } else {
                echo "❌ Welcome menu failed\n";
            }

            // Test language selection
            $menu = new Menu($this->testSessionId, $this->testPhoneNumber, '1');
            $response = $menu->processMenu();
            
            if (strpos($response, 'CON') === 0) {
                echo "✅ Language selection works\n";
            } else {
                echo "❌ Language selection failed\n";
            }

            // Test main menu (after language selection)
            $menu = new Menu($this->testSessionId, $this->testPhoneNumber, '1*1');
            $response = $menu->processMenu();
            
            if (strpos($response, 'CON') === 0) {
                echo "✅ Main menu works\n";
            } else {
                echo "❌ Main menu failed\n";
            }

        } catch (Exception $e) {
            echo "❌ USSD menu flow error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    public function testSMSFunctionality() {
        echo "📧 Testing SMS Functionality...\n";
        try {
            require_once 'sms.php';
            $sms = new Sms();
            
            // Test SMS sending (commented out to avoid actual SMS)
            // $result = $sms->sendRegistrationSMS($this->testPhoneNumber, 'Test User', 'farmer');
            // echo $result ? "✅ SMS sending works\n" : "❌ SMS sending failed\n";
            
            echo "✅ SMS class instantiation successful\n";
        } catch (Exception $e) {
            echo "❌ SMS functionality error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    public function cleanup() {
        echo "🧹 Cleaning up test data...\n";
        try {
            // Clean up test user
            $user = $this->db->getUserByPhone($this->testPhoneNumber);
            if ($user) {
                // Delete test products
                $stmt = $this->db->getConnection()->prepare("DELETE FROM crops WHERE farmer_id = ?");
                $stmt->execute([$user['id']]);
                
                // Delete test user
                $stmt = $this->db->getConnection()->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                echo "✅ Test data cleaned up\n";
            }
        } catch (Exception $e) {
            echo "❌ Cleanup error: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
}

// Run tests if script is executed directly
if (php_sapi_name() === 'cli') {
    $tester = new USSDTester();
    $tester->runTests();
    $tester->testSMSFunctionality();
    $tester->cleanup();
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test_ussd.php\n";
}
?>
