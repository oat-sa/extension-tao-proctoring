<p>{{__ "If you continue, these deliveries will be missing from the report."}}</p>

<ul>
{{#each excluded}}
    <li>{{ label }} ({{ date }})</li>
{{/each}}
</ul>