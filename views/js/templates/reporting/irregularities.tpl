<ul class="irregularities plain">
{{#each this}}
    <li class="irregularity">
        <span class="timestamp">{{timestamp}}</span>
        <span class="type">{{type}}</span>
        {{#if reason}}<span class="reason">[{{reason}}</span>]{{/if}}
        {{#if comment}}- <span class="comment">{{comment}}</span>{{/if}}
    </li>
{{/each}}
</ul>