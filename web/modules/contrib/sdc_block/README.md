# SDC Block (sdc_block)

This module lets you put [Single Directory Components](https://www.drupal.org/docs/develop/theming-drupal/using-single-directory-components)
in the page using blocks. This includes the regular block layout, layout
builder, and any other tool that renders blocks in a page.

### Configurable blocks

This module will provide one block type for each component tagged using SDC
Tagging. A configuration form will be auto-generated for your block, even if
your component uses props or contains slots. **No additional effort required**.

[![Auto-generated forms](https://www.drupal.org/files/Screenshot%20from%202022-07-20%2012-39-52.png)](https://www.drupal.org/files/Screenshot%20from%202022-07-20%2012-39-52.png)

Note in the screenshot above that the component contains one _string_ property
called _Heading_. It also contains one slot called `card_body`.

### Token support

When a site builder puts a block on a page that contains a **node**, then they
can use the node information in the CL Block using tokens. Access the node
content (and its relationships) using tokens. For instance: **`[node:title]`**.
