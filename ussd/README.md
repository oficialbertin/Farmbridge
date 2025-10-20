# FarmBridge AI USSD Application

A comprehensive USSD (Unstructured Supplementary Service Data) application for FarmBridge AI that enables farmers and buyers to access agricultural services through any mobile phone without internet connectivity.

## 🌾 Overview

The FarmBridge AI USSD platform makes essential farming and trading services accessible to all farmers and buyers — even those without smartphones or internet access. Through a simple mobile code (accessible via Africa's Talking gateway), users can interact with FarmBridge AI using any type of phone.

## ✨ Key Features

### User Registration & Management
- **Farmer Registration**: Complete profile setup with location details
- **Buyer Registration**: Quick registration for product buyers
- **Profile Management**: Update personal information and preferences
- **Language Support**: English and Kinyarwanda

### Product Management
- **Product Listing**: Farmers can list available produce with details
- **Product Browsing**: Buyers can browse and search available products
- **Order Management**: Complete order creation and tracking
- **Inventory Management**: Real-time quantity updates

### Market Intelligence
- **Market Prices**: View up-to-date crop prices across locations
- **Price Trends**: Historical price analysis and trends
- **Price Alerts**: Get notified when prices reach target levels
- **Market Recommendations**: AI-powered trading suggestions

### Farming Support
- **Daily Tips**: Get daily farming advice and tips
- **Seasonal Guidance**: Season-specific farming recommendations
- **Crop-Specific Tips**: Targeted advice for specific crops
- **Weather-Based Tips**: Weather condition-specific guidance

### Communication
- **SMS Notifications**: Automated SMS for important updates
- **Order Confirmations**: Real-time order status updates
- **Price Alerts**: Market price change notifications
- **Farming Tips**: Daily tip delivery via SMS

## 🏗️ Architecture

### Core Components

1. **index.php** - Main entry point for USSD requests
2. **menu.php** - USSD menu navigation and logic
3. **util.php** - Configuration and utility functions
4. **database.php** - Database operations and connections
5. **sms.php** - SMS functionality via Africa's Talking
6. **user_management.php** - User registration and management
7. **product_management.php** - Product listing and buying
8. **market_prices.php** - Market price viewing and analysis
9. **farming_tips.php** - Farming tips and advice

### Database Schema

The application uses the existing FarmBridge AI database with additional tables for USSD sessions and market prices.

#### Key Tables:
- `users` - User accounts and profiles
- `crops` - Product listings
- `orders` - Order management
- `market_prices` - Market price data
- `farming_tips` - Farming advice and tips
- `ussd_sessions` - USSD session management
- `payments` - Payment tracking
- `disputes` - Dispute resolution

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- Africa's Talking account

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd FarmBridgeAI/ussd
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Update database credentials in `util.php`
   - Ensure database tables exist (use existing FarmBridge AI schema)

4. **Configure Africa's Talking**
   - Update API credentials in `util.php`
   - Set up USSD service code with Africa's Talking

5. **Deploy to server**
   - Upload files to web server
   - Configure web server to handle USSD requests
   - Set up SSL certificate for secure connections

## 📱 Usage

### USSD Flow

1. **User dials USSD code** (e.g., *384*123#)
2. **Language selection** - Choose English or Kinyarwanda
3. **Registration** (if new user) or **Main menu** (if existing user)
4. **Feature access** - Browse products, view prices, get tips, etc.

### Menu Structure

#### Main Menu (Farmers)
```
1. List Product
2. My Products
3. My Orders
4. Market Prices
5. Farming Tips
6. Profile
7. Help
```

#### Main Menu (Buyers)
```
1. Browse Products
2. My Orders
3. Market Prices
4. Farming Tips
5. Profile
6. Help
```

### Navigation
- `98` - Go back to previous menu
- `99` - Return to main menu

## 🔧 Configuration

### Environment Variables

Update the following in `util.php`:

```php
// Database configuration
static $host = 'localhost';
static $db = 'farmbridge_ai';
static $user = 'root';
static $pass = '';

// SMS configuration
static $username = "sandbox";
static $apikey = "your_api_key_here";
static $Company = "FarmBridge AI";
static $short_code = 4627;
```

### Africa's Talking Setup

1. Create account at [Africa's Talking](https://africastalking.com)
2. Get API credentials
3. Set up USSD service
4. Configure callback URL to point to your `index.php`

## 📊 Features in Detail

### User Registration
- Multi-step registration process
- Location-based registration (Province, District, Sector)
- Role selection (Farmer/Buyer)
- SMS confirmation upon successful registration

### Product Management
- **For Farmers**: List products with quantity, price, and description
- **For Buyers**: Browse available products, view details, place orders
- Real-time inventory updates
- Product search and filtering

### Market Prices
- Current market prices for common crops
- Price trends and historical data
- Location-based price comparison
- Price alerts and notifications

### Farming Tips
- Daily farming tips
- Seasonal advice
- Crop-specific guidance
- Weather-based recommendations
- Pest and disease management tips

### Order Management
- Order creation and tracking
- Status updates (Pending, Confirmed, Delivered, Cancelled)
- SMS notifications for order updates
- Dispute resolution system

## 🔒 Security

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- Session management
- Phone number validation
- Email validation

### Access Control
- User authentication
- Session timeout
- Role-based access control
- Input validation

## 📈 Performance

### Optimization Features
- Database connection pooling
- Session cleanup
- Efficient query optimization
- Caching mechanisms
- Error handling and logging

### Monitoring
- Error logging
- Performance metrics
- User activity tracking
- SMS delivery tracking

## 🧪 Testing

### Test Coverage
- Unit tests for core functions
- Integration tests for database operations
- USSD flow testing
- SMS functionality testing

### Running Tests
```bash
composer test
```

## 📝 API Documentation

### USSD Endpoints

#### Main Entry Point
- **URL**: `/ussd/index.php`
- **Method**: POST
- **Parameters**:
  - `sessionId` - Unique session identifier
  - `serviceCode` - USSD service code
  - `phoneNumber` - User's phone number
  - `text` - User input text

#### Response Format
- **CON** - Continue session (show menu)
- **END** - End session (show final message)

### SMS Integration

#### Africa's Talking SMS
- Automated SMS notifications
- Order confirmations
- Price alerts
- Farming tips delivery

## 🚀 Deployment

### Production Deployment

1. **Server Requirements**
   - PHP 7.4+
   - MySQL 5.7+
   - SSL certificate
   - Web server (Apache/Nginx)

2. **Configuration**
   - Update production database credentials
   - Configure Africa's Talking production API
   - Set up monitoring and logging
   - Configure backup procedures

3. **Monitoring**
   - Error logging
   - Performance monitoring
   - User activity tracking
   - SMS delivery monitoring

## 🤝 Contributing

### Development Guidelines
- Follow PSR-4 autoloading standards
- Use meaningful variable and function names
- Add comments for complex logic
- Write unit tests for new features
- Follow existing code style

### Pull Request Process
1. Fork the repository
2. Create feature branch
3. Make changes
4. Add tests
5. Submit pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

### Getting Help
- Check the documentation
- Review existing issues
- Contact the development team
- Submit bug reports

### Contact Information
- **Email**: support@farmbridgeai.com
- **Phone**: +250 788 123 456
- **Website**: https://farmbridgeai.com

## 🔄 Version History

### v1.0.0 (Current)
- Initial release
- Basic USSD functionality
- User registration and management
- Product listing and buying
- Market price viewing
- Farming tips
- SMS notifications

### Planned Features
- Payment integration
- Advanced analytics
- Weather integration
- Crop disease detection
- Market forecasting
- Multi-language support expansion

## 📚 Additional Resources

### Documentation
- [Africa's Talking USSD Documentation](https://developers.africastalking.com/ussd)
- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Tools
- [Composer](https://getcomposer.org/)
- [PHPUnit](https://phpunit.de/)
- [VS Code](https://code.visualstudio.com/)

---

**FarmBridge AI USSD Application** - Connecting farmers and buyers through technology, one call at a time.
