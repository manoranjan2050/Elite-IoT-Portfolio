-- ============================================================
-- manoranjan.dev — Database Migration
-- Run this SQL in your Hostinger phpMyAdmin or MySQL client
-- ============================================================

-- 1. Enhance admin_users with profile fields
ALTER TABLE admin_users
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) DEFAULT NULL AFTER username,
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL AFTER full_name,
    ADD COLUMN IF NOT EXISTS mobile VARCHAR(20) DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL AFTER mobile,
    ADD COLUMN IF NOT EXISTS bio TEXT DEFAULT NULL AFTER profile_photo;

-- 2. Home Assistant global settings table (key-value store)
CREATE TABLE IF NOT EXISTS ha_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default HA settings (edit these from the admin panel)
INSERT IGNORE INTO ha_settings (setting_key, setting_value) VALUES
    ('ha_url',           'https://power.mtpretails.in'),
    ('ha_token',         ''),
    ('ha_enabled',       '1'),
    ('site_a_name',      'Shop'),
    ('site_b_name',      'Home'),
    ('control_pattern',  ''); -- empty = PHP uses default '1235'; change via Admin > HA Settings

-- 3. HA entities table — manage all entity IDs from admin
CREATE TABLE IF NOT EXISTS ha_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_key VARCHAR(100) NOT NULL UNIQUE COMMENT 'JS variable name, e.g. shop_pv',
    entity_id  VARCHAR(200) NOT NULL COMMENT 'HA entity ID, e.g. sensor.flin_energy_pv_power',
    friendly_name VARCHAR(100) DEFAULT NULL,
    entity_type ENUM('sensor','switch','light','binary_sensor','automation','scene','other') DEFAULT 'sensor',
    site ENUM('shop','home','global') DEFAULT 'shop',
    display_unit VARCHAR(20) DEFAULT NULL,
    show_in_control TINYINT(1) DEFAULT 0 COMMENT 'Show in control panel',
    show_in_power   TINYINT(1) DEFAULT 1 COMMENT 'Show in power.php',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default entities (same as current hardcoded ones in power.php)
INSERT IGNORE INTO ha_entities (entity_key, entity_id, friendly_name, entity_type, site, display_unit, show_in_control, show_in_power, display_order) VALUES
    ('shop_total_soc',   'sensor.shop_total_soc',                                          'Shop Total SOC',          'sensor',        'shop',   '%',   0, 1,  1),
    ('shop_total_ah',    'sensor.shop_total_capacity_ah',                                   'Shop Total Capacity',     'sensor',        'shop',   'Ah',  0, 1,  2),
    ('shop_total_amps',  'sensor.shop_total_current',                                       'Shop Total Current',      'sensor',        'shop',   'A',   0, 1,  3),
    ('shop_backup',      'sensor.shop_backup_time_remaining',                               'Shop Backup Time',        'sensor',        'shop',   '',    0, 1,  4),
    ('shop_to_full',     'sensor.shop_time_to_full_charge',                                 'Shop Time to Full',       'sensor',        'shop',   '',    0, 1,  5),
    ('shop_pv',          'sensor.flin_energy_pv_power',                                     'Shop Solar PV',           'sensor',        'shop',   'W',   0, 1,  6),
    ('shop_load',        'sensor.flin_energy_load_power',                                   'Shop Load Power',         'sensor',        'shop',   'W',   0, 1,  7),
    ('shop_temp',        'sensor.flin_energy_battery_temperature',                          'Shop Battery Temp',       'sensor',        'shop',   '°C',  0, 1,  8),
    ('shop_grid',        'sensor.flin_energy_grid_power',                                   'Shop Grid Power',         'sensor',        'shop',   'W',   0, 1,  9),
    ('shop_batt_pwr',    'sensor.flin_energy_battery_power',                                'Shop Battery Power',      'sensor',        'shop',   'W',   0, 1, 10),
    ('shop_p1_soc',      'sensor.shop_battery_pack_one_shop_bms_state_of_charge',           'Pack 1 SOC',              'sensor',        'shop',   '%',   0, 1, 11),
    ('shop_p1_amps',     'sensor.shop_battery_pack_one_shop_bms_current',                   'Pack 1 Current',          'sensor',        'shop',   'A',   0, 1, 12),
    ('shop_p1_delta',    'sensor.shop_battery_pack_one_shop_bms_cell_delta',                'Pack 1 Cell Delta',       'sensor',        'shop',   'V',   0, 1, 13),
    ('shop_p1_link',     'binary_sensor.shop_battery_pack_one_shop_bms_online_status',      'Pack 1 Online',           'binary_sensor', 'shop',   '',    0, 1, 14),
    ('shop_p1_sw_c',     'switch.shop_battery_pack_one_shop_bms_charging_switch',           'Pack 1 Charge Switch',    'switch',        'shop',   '',    1, 1, 15),
    ('shop_p1_sw_d',     'switch.shop_battery_pack_one_shop_bms_discharging_switch',        'Pack 1 Discharge Switch', 'switch',        'shop',   '',    1, 1, 16),
    ('shop_p2_soc',      'sensor.shop_battery_pack_two_shop_2_bms_state_of_charge',         'Pack 2 SOC',              'sensor',        'shop',   '%',   0, 1, 17),
    ('shop_p2_amps',     'sensor.shop_battery_pack_two_shop_2_bms_current',                 'Pack 2 Current',          'sensor',        'shop',   'A',   0, 1, 18),
    ('shop_p2_delta',    'sensor.shop_battery_pack_two_shop_2_bms_cell_delta',              'Pack 2 Cell Delta',       'sensor',        'shop',   'V',   0, 1, 19),
    ('shop_p2_link',     'binary_sensor.shop_battery_pack_two_shop_2_bms_online_status',    'Pack 2 Online',           'binary_sensor', 'shop',   '',    0, 1, 20),
    ('shop_p2_sw_c',     'switch.shop_battery_pack_two_shop_2_bms_charging_switch',         'Pack 2 Charge Switch',    'switch',        'shop',   '',    1, 1, 21),
    ('shop_p2_sw_d',     'switch.shop_battery_pack_two_shop_2_bms_discharging_switch',      'Pack 2 Discharge Switch', 'switch',        'shop',   '',    1, 1, 22),
    ('home_pv',          'sensor.q004719472515009ad05_direct_pv_power',                     'Home Solar PV',           'sensor',        'home',   'W',   0, 1, 23),
    ('home_soc',         'sensor.jkbms_home_bms_state_of_charge',                           'Home BMS SOC',            'sensor',        'home',   '%',   0, 1, 24),
    ('home_v',           'sensor.jkbms_home_bms_battery_voltage',                           'Home BMS Voltage',        'sensor',        'home',   'V',   0, 1, 25),
    ('home_p',           'sensor.jkbms_home_bms_power',                                     'Home BMS Power',          'sensor',        'home',   'W',   0, 1, 26),
    ('home_load',        'sensor.q004719472515009ad05_direct_inverter_out_power',            'Home Load Power',         'sensor',        'home',   'W',   0, 1, 27),
    ('home_temp',        'sensor.jkbms_home_bms_temperature_1',                             'Home BMS Temp',           'sensor',        'home',   '°C',  0, 1, 28),
    ('home_delta',       'sensor.jkbms_home_bms_cell_delta',                                'Home Cell Delta',         'sensor',        'home',   'mV',  0, 1, 29),
    ('home_grid',        'sensor.q004719472515009ad05_direct_apparent_power',               'Home Grid Power',         'sensor',        'home',   'W',   0, 1, 30),
    ('home_batt_pwr',    'sensor.jkbms_home_bms_power',                                     'Home Battery Power',      'sensor',        'home',   'W',   0, 1, 31),
    ('home_amps',        'sensor.jkbms_home_bms_current',                                   'Home BMS Current',        'sensor',        'home',   'A',   0, 1, 32);
