<?php
if (isset($_GET['customer']) && file_exists(__DIR__ . '/csv/sib_import_customers.csv')) {
	//unlink(__DIR__ . '/csv/sib_import_customers.csv');
}

if (isset($_GET['order']) && file_exists(__DIR__ . '/csv/sib_import_orders.csv')) {
	//unlink(__DIR__ . '/csv/sib_import_orders.csv');
}