# REST API

## IN PROGRESS

This extension is now rather broken. We are working on making it plugin-based for greater extensibility. Please do not use.

## DISCLAIMER

This extension is entirely in BETA and should NOT find its way near a production environment. There may well be security holes or bugs which could compromise the integrity of your data. (For which I hold no responsibility. Thx.)

## TODO
* add maximum values for GET parameters to prevent malicious overloading (`limit`, `include` etc.)
* add support for other output formats namely CSV for Data Source output

## Installation

1. Download the `rest_api` extension and add it to your extensions folder.
2. Enable the extension from the System > Extensions page in Symphony.
3. Replace `/symphony/lib/toolkit/class.xmlelement.php` with the one included with this extension. (A temporary fix until merged into the core.)

## API usage

The API provides access to Symphony via read queries (a Data Source) and create/update (Events). The following methods are suported:

### Querying a Section (GET)

Most simply a section can be queried passing the section handle:
	
	/symphony/api/:section

This returns a standard XML result from a Data Source. Querystring parameters can be added for finer control:

* `include` a comma-delimited list of "Included Elements" (XML element names) to include for each entry
* `limit` the number of entries to return
* `page` the page number, if pagination is being used
* `sort` the field (XML element name) to sort on
* `order` the sort direction (asc, desc, rand)
* `groupby` the field (XML element name) to group by

For example to get a list of the latest 5 entries from a section "Articles":

	/symphony/api/articles/?include=title,body,date&limit=5&sort=date&order=desc

Pagination can be returned by adding `system:pagination` to the value of the `elements` list e.g.

	/symphony/api/articles/?include=title,body,system:pagination

### Querying a specific entry (GET)

A known entry can be returned by passing the section handle and entry ID:

	/symphony/api/:section/:entry_id

### Filtering (GET)
Presently filters are not supported.

### Creating a new entry (POST)
To create or update entries you can send an HTTP POST to the section URL:

	/symphony/api/:section

The format of the POST should follow exactly the field names for a normal event, i.e. `fields[title]`. Multiple entries can be created or updated by sending arrays of fields e.g. `fields[0][title]`, `fields[1][title]` which the API will detect automatically.

### Updating an existing entry (POST)
You can update an existing entry in one of two ways:

a. As with normal Events, include an `id` variable in your POST containing the value of the entry ID to update and post to the section URL:
		
	/symphony/api/:section

b. Omit the `id` variable from your POST variables itself and send your request to:

	/symphony/api/:section/:entry_id

## Response formats
By default the API returns XML but JSON, YAML and serialised PHP arrays are also supported by appending the `output` variable:

	/symphony/api/articles/?output=xml
	/symphony/api/articles/?output=json
	/symphony/api/articles/?output=yaml
	/symphony/api/articles/?output=serialise

## Authentication and security

By default the API is private. You authenticate in one of two ways:

a. By logging-in to Symphony and possessing an Author cookie in your browser.

b. Pass a `token` value in the call to the API (either GET or POST). The token is the hash portion of your "remote login" URL for your user account. This only works when "allow remote login" is enabled. For example:

	/symphony/api/:section/?token=8ca221bb

If you "Enable public access" via System > Preferences you can choose which sections are viewable via the API without authentication.