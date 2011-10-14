# REST API

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
2. Pass a `auth-token` querystring value in the call to the API (either GET querystring or POST values). The token is the hash portion of your "remote login" URL for your user account. This only works when "allow remote login" is enabled.

An example token might look like this. It needs to be passed with every request.

	/symphony/api/entries/articles/?auth-token=8ca221bb

I suggest sandboxing the API to a single user account. I usually create an "API" user in Symphony and use this author's token for all requests.

### Authors plugin

The Authors plugin provides information about your Symphony authors (user accounts).

To **list all authors**:

	/symphony/api/authors

To **read a specific author**, pass the author ID or username:

	/symphony/api/authors/1
	/symphony/api/authors/nickdunn

Example XML response looks like:

	<response>
		<author>
			<id>1</id>
			<username>nickdunn</username>
			<password>b94a8fe5cab12ba61c4c9973d391e987982fccd4</password>
			<first-name>Nick</first-name>
			<last-name>Dunn</last-name>
			<email>nick@domain.com</email>
			<last-seen>2011-03-28 07:45:14</last-seen>
			<user-type>developer</user-type>
			<primary>yes</primary>
			<auth-token-active>yes</auth-token-active>
		</author>
	</response>

### Sections plugin

Incomplete documentation. Eventually similar documentation to Section Schemas extension.

### Entries plugin

The Entries plugin provides the same functionality of data sources (read entries) and events (create and update entries).

To **list entries** from a section:

	/symphony/api/entries/:section_handle

To **read a specific entry** from a section:

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
* `order` the sort direction (`asc`, `desc`, `rand`)
* `groupby` the field handle (XML element name) to group by

For example to get a list of the latest 5 entries from a section "Articles":

	/symphony/api/entries/articles/?fields=title,body,date&limit=5&sort=date&order=desc

Pagination can be returned by adding `system:pagination` to the value of the `fields` list e.g.

	/symphony/api/entries/articles/?fields=title,body,system:pagination

Additionally you can **filter entries** using data source filtering syntax. Use a `filter` array in the querystring:

	/symphony/api/entries/articles/?filter[title]=regexp:Nick&filter[date]=later+than+today

To **create an entry** you can send an HTTP POST to the section URL. The format of the POST should follow exactly the field names for a normal Symphony event, i.e. `fields[title]`. For example:

	<form method="post" action="/symphony/api/entries/articles">
		<input name="fields[title]" />
		<textarea name="fields[content]"></textarea>
		<input type="submit" />
	</form>

The XML result looks like:

	<response id="..." result="success" type="created">
		<message>Entry created successfully.</message>
		<post-values>
			<title>...</title>
			<content>...</title>
		</post-values>
	</response>

Multiple entries can be created by sending arrays of fields e.g. `fields[0][title]`, `fields[1][title]`, just as with a normal Symphony event.

To update an existing entry, you have two options. Either include an `id` in the POST array and post it to the section handle (as above), or omit the `id` and post to the entry URL directly. For example:

	/symphony/api/entries/articles/31

The XML response looks like:

	<response id="31" result="success" type="edited">
		<message>Entry edited successfully.</message>
		<post-values>
			...
		</post-values>
	</response>

To **delete an entry** send an HTTP DELETE request to the entry's URL (the same URL as if reading the entry's data), such as:

	curl -X DELETE /symphony/api/entries/articles/123

The XML response looks like:

	<response id="123" result="success" type="deleted">
		<message>Entry deleted successfully.</message>
	</response>


## Anatomy of an API plugin

A plugin follows the following naming convention:

	/extensions/rest_api/plugins/{name}/rest.{name}.php

This file should contain a class named `REST_{Name}`.

Each plugin class can implement six public methods. All are optional (but omitting all of them would lead to a pretty useless plugin...).

### `init()`

This is the first plugin function that is called on each request. It is used to build objects and do any initial work that may be required for the additional methods.

### `authenticate()`

If you need any custom authentication rules, put them here. Perhaps you want to limit your plugin access to a specific user account, or Developers only. You should restrict access by sending a 403:

	REST_API::sendError("You are not permitted to view this plugin.", 403);

This will terminate the response and send an error to the client.

The remaining four methods represent the four HTTP methods (GET, POST, PUT and DELETE). If the method is omitted from the plugin, an "unsupported" response will be returned to the client.

### `get()`

This function is run when the client performs a GET request to your plugin. This is for read-only requests.

### `post()`

This function is run when the client performs a POST request to your plugin. This is for write operations, to create a new or update existing resource.

### `put()`

This function is run when the client performs a PUT request to your plugin. This is for write operations, to create a new resource.

### `delete()`

This function is run when the client performs a DELETE request to your plugin. This is for write operations to delete a resource.

## Shell access

If you use the [Symphony Shell extension](https://github.com/pointybeard/shell) you can access the REST API on the command line. It's no longer using HTTP, but access is the same. An HTTP request that previously looked like:

	/symphony/api/entries/articles/2/?format=json&method=get
	
Maps on to the following Shell command:

	php symphony -t {token} rest_api request -path entries/articles/2 -format json -method get

An explanation of the above:

* `php symphony -t {token}` is saying use `php` to instantiate the `symphony` script, passing `{token}` (replace this with an Author access token)
* `rest_api request` is saying use the `request` script from the `rest_api` extension
* `-path the/path/here` passes the REST URL as if you were using the REST API via HTTP
* `-format json` is the equivalent of `?format=json`
* `-method get` is the equivalent of an HTTP GET