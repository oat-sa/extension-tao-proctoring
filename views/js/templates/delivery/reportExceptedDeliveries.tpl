<p>{{__ "If you continue, these deliveries will be missing from the report."}}</p>

<ul>
{{#each excepted}}
    <li>{{ label }} ({{ date }})</li>
{{/each}}
</ul>