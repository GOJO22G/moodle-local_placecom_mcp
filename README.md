# local_placecom_mcp

Unified Moodle plugin combining OAuth2 authorization server, OAuth-to-webservice
token bridging, and MCP (Model Context Protocol) server functionality — replacing
the previously separate `local_oauth2`, `local_mcpbridge`, and `webservice_mcp`
plugins with one codebase, published under Placecom (AlmaBay Networks Pvt. Ltd.).

**Status: Phase 1 of 7 — scaffold only.** Capabilities and database schema are
merged and renamed. OAuth2 core, MCP server logic, and the token bridge itself
have not yet been ported. Do not install on a production Moodle site yet.

## Origin

This plugin is a merge and continuation of three separately maintained plugins:

- **OAuth2 core** is based on work by Enovation Solutions
  ([local_oauth2](https://moodle.org/plugins/local_oauth2)), originally forked
  from `projectestac/moodle-local_oauth`. Substantially modified for this
  project (PKCE, wider token columns, SHA-256 token hashing, MCP-specific
  scopes, token revocation).
- **MCP server** is based on work by MohammadReza PourMohammad
  (`onbirdev/moodle-webservice_mcp`).
- **Token bridging logic** was originally a separate plugin, `local_mcpbridge`,
  built by Placecom; its token-mirroring approach is being retired as part of
  this merge, now that OAuth and webservice tokens are issued by the same
  plugin.

See `LICENSE` for full terms (GPL v3 or later). Per-file `@copyright` headers
preserve original authorship alongside Placecom's own.

## Merge plan

See the 7-phase plan tracked internally (scaffold → capabilities → database →
OAuth2 core → MCP server → bridge removal → testing). This repo is currently
at the end of Phase 1.

## Requirements

Moodle 4.5+ (`$plugin->requires = 2024100700`).

## Development

Clone into `<moodledir>/local/placecom_mcp`. Run `php admin/cli/upgrade.php`
to install/upgrade.
