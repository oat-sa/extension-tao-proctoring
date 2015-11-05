<span class="progress">
{{#with section}}
    {{#if position}}<span class="state section-progress">{{__ 'section'}} {{position}}{{#if total}}/{{total}}{{/if}}</span>{{/if}}
    {{#if label}}<span class="state section-label">{{label}}</span>{{/if}}
{{/with}}
{{#with item}}
    {{#if position}}<span class="state item-progress">{{__ 'item'}} {{position}}{{#if total}}/{{total}}{{/if}}</span>{{/if}}
    {{#with time}}
        {{#if display}}
    <span class="item-time">
            {{#if elapsedStr}}<span class="elapsed" title="{{__ 'Elapsed time'}}">{{elapsedStr}}</span>{{/if}}
        <span class="icon icon-time"></span>
            {{#if remainingStr}}<span class="remaining" title="{{__ 'Remaining time'}}">{{remainingStr}}</span>{{/if}}
    </span>
        {{/if}}
    {{/with}}
{{/with}}
</span>