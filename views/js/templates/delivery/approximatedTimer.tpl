{{#if timer}}
<span class="approximated-timer" {{#if since}} title="{{since}}"{{/if}}>~{{timer}}</span>
{{#if since}}<span class="icon-warning" title="{{since}}"></span>{{/if}}
{{/if}}
