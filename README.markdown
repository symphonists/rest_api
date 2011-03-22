# REST API

* Version: 1.0.0
* Author: Nick Dunn <http://github.com/nickdunn/>
* Build Date: 2011-03-22
* Requirements: Symphony 2.2

## Installation

1. Download the `rest_api` extension and add it to your extensions folder
2. Enable the extension from the System > Extensions page in Symphony

## Usage

The API is a series of plugins (in the `plugins`) folder which control the read/write of data from various parts of Symphony. There are presently three plugins:

* Authors: read meta data about authors
* Entries: read and write entries
* Sections: read meta data about sections and fields

An API plugin is instantiated using then following URL:

    /symphony/api/:plugin

For example the root of the Authors API plugin is at:

	/symphony/api/authors

Details on how to create plugins is included later in this README.

## Response formats
By default the API returns XML but JSON, YAML and serialised PHP arrays are also supported by appending the `format` variable to any URL.

	/symphony/api/entries/articles/?format=xml
	/symphony/api/entries/articles/?format=json
	/symphony/api/entries/articles/?format=yaml
	/symphony/api/entries/articles/?format=serialise

## Authentication and security

The API is private. You must authenticate as a Symphony author in one of two ways:

1. Log in to Symphony (obtain the cookie) and send that with your request
2. Pass a `token` querystring value in the call to the API (either GET or POST). The token is the hash portion of your "remote login" URL for your user account. This only works when "allow remote login" is enabled.

An example token might look like this. It needs to be passed with every request.

	/symphony/api/entries/articles/?token=8ca221bb

### Authors plugin

Incomplete.

### Sections plugin

Incomplete.

### Entries plugin

The Entries plugin provides the same functionality of data sources (read entries) and events (create and update entries).

To read entries from a section:

	/symphony/api/entries/:section_handle

To read a specific entry from a section:

	/symphony/api/entries/:section_handle/:entry_id

The default XML response looks like:

	<response>
		<pagination total-entries="3" total-pages="1" entries-per-page="10" current-page="1"/>
		<section id="1" handle="articles">Articles</section>
		<entry id="1">
			...
		</entry>
		<entry id="2">
			...
		</entry>
		<entry id="3">
			...
		</entry>
	</response>

When reading entries from a section, querystring parameters can be added for finer control:

* `fields` a comma-delimited list of field handles (XML element names) to include for each entry
* `limit` the number of entries to return per page
* `page` the page number, if pagination is being used
* `sort` the field handle (XML element name) to sort by
* `order` the sort direction (asc, desc, rand)
* `groupby` the field handle (XML element name) to group by

For example to get a list of the latest 5 entries from a section "Articles":

	/symphony/api/entries/articles/?fields=title,body,date&limit=5&sort=date&order=desc

Pagination can be returned by adding `system:pagination` to the value of the `fields` list e.g.

	/symphony/api/entries/articles/?fields=title,body,system:pagination

Additionally you can filter entries using data source filtering syntax. Use a `filters` array in the querystring:

	/symphony/api/entries/articles/?filters[title]=regexp:Nick&filters[date]=later+than+today

To create an entry you can send an HTTP POST to the section URL. The format of the POST should follow exactly the field names for a normal Symphony event, i.e. `fields[title]`. For example:

	<form method="post" action="/symphony/api/entries/articles">
		<input name="fields[title]" />
		<textarea name="fields[content]"></textarea>
		<input type="submit" />
	</form>

Multiple entries can be created by sending arrays of fields e.g. `fields[0][title]`, `fields[1][title]`, just as with a normal Symphony event.

To update an existing entry, you have two options. Either include an `id` in the POST array and post it to the section handle, or omit the `id` and post to the entry URL directly. For example:

	/symphony/api/entries/articles/31


## Anatomy of an API plugin

A plugin follows the following naming convention:

	/extensions/rest_api/plugins/{name}/rest.{name}.php

This file should contain a class named `REST_{Name}`.

Each plugin class can implement four public methods. All are optional (but omitting all of them would lead to a pretty useless plugin...).

### `init()`

This is the first plugin function that is called on each request. It is used to build objects and do any initial work that may be required for the additional methods.

### `authenticate()`

If you need any custom authentication rules, put them here. Perhaps you want to limit your plugin access to a specific user account, or Developers only. You should restrict access by sending a 403:

	REST_API::sendError("You are not permitted to view this plugin.", 403);

This will terminate the response and send an error to the client.

### `get()`

This function is run when the client performs a GET request to your plugin. This is for read-only requests. You have full access to the `$_GET` or `$_REQUEST` data.

### `post()`

This function is run when the client performs a POST request to your plugin. This is for write operations. You have full access to the `$_GET` or `$_REQUEST` data.

## Todo

Here's a quick list of things that have yet to be implemented:

* CSV export type (if there's a good library to convert an array into a CSV)
* Author plugin
* Section plugin no longer tries to use a field's `displayPublishPanel` as this is unstable for some extensions. Unfortunately this means it cannot return default values for fields (like the Section Schemas extension)
* add maximum values for GET parameters to prevent malicious overloading (`limit`, `include` etc.)
* caching. Perhaps simplest to use HTTP caching and respect 304 Not Modified requests somehow