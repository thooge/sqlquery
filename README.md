# sqlquery
A dokuwiki plugin for processing query to MySQL databases and display results as a table.

This is a improved version which cann connect to different hosts
and databases. The default host and database is set in the plugin
configuration.
Different hosts and databases can be set inside the tag:
```
<sql host=myhost db=mydb>
SELECT foo FROM bar ORDER BY baz
<sql>
```

More information at https://www.dokuwiki.org/plugin:sqlquery
