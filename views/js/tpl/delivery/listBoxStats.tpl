{{#if showProperties}}
<ul class="plain listbox-properties">
    <li>
        <label>{{__ "Start"}}</label>: {{periodStart}}
    </li>
    <li>
        <label>{{__ "End"}}</label>: {{periodEnd}}
    </li>
</ul>
{{/if}}
<p class="listbox-stats">
    <span class="icon-lock"></span><span class="number">{{locked}}</span>
    <span class="icon-play"></span><span class="number">{{inProgress}}</span>
    <span class="icon-pause"></span><span class="number">{{paused}}</span>
</p>