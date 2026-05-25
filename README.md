# Woo Sales Booster

A lightweight WooCommerce plugin that bundles 3 powerful sales-boosting features into one easy-to-manage solution.

## Features

### 📱 WhatsApp Order Button
Allow customers to place or inquire about orders directly via WhatsApp. A click-to-chat button appears on product pages, making it easier for customers to reach you instantly.

### 💰 Cash on Delivery (COD) Extra Fee
Automatically apply a custom extra fee when customers choose Cash on Delivery as their payment method. Helps offset handling costs and encourages digital payments.

### 🎁 Buy X Get Y (BXGY) Discount
Set up smart cart-level discount rules — reward customers who buy a certain quantity with free or discounted items. Includes a dynamic cart progress bar to encourage higher order values.

## Requirements
- WordPress 6.0+
- WooCommerce 7.0+
- PHP 7.4+

## Installation
1. Download the plugin zip file
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Upload the zip and click **Install Now**
4. Activate the plugin
5. Go to **WooCommerce → Sales Booster** to configure each module

## Plugin Structure
```
woo-sales-booster/
├── admin/                  # Admin panel & settings UI
├── modules/
│   ├── whatsapp/           # WhatsApp button module
│   ├── cod/                # COD extra fee module
│   └── bxgy/               # Buy X Get Y discount module
├── templates/              # Frontend tab templates
├── woo_sales_booster.php   # Main plugin file
├── woo_activator.php       # Activation hook
└── woo_deactivator.php     # Deactivation hook
```

## Status
✅ Phase 1 Complete — WhatsApp Button  
✅ Phase 2 Complete — COD Extra Fee  
✅ Phase 3 Complete — Buy X Get Y Discount  

## Author
MrHafi

## License
GPL v2 or later
