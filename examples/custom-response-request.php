<?php

/**
 * Slice Framework (http://sliceframework.com)
 * Copyright (C) 2013 devSDMF Software Development Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 */

/**
 * Slice Framework - Example File
 *
 * Performing a request using a custom response handler
 */

use Slice\Http\Client;
use Vendor\Package\CustomResponse;

# Initializing Client
$client = new Client();

# Setting a custom response handler (response handler must be a inheritance of Slice\Http\ResponseAbstract)
$client->setResponseHandler(CustomResponse::getHandler());

# Set URI
$client->setUri('http://localhost/ws/');

# Performing request
$response = $client->request();

# $response is a instance of CustomResponse class configured in Client request method