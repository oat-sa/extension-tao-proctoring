{{#if byPassProctor}}
    <span class="icon-unshield txt-danger" title="{{__ 'This delivery can be launched without authorization'}}"></span>
{{else}}
    <span class="icon-shield txt-success" title="{{__ 'This delivery needs proctor authorization'}}"></span>
{{/if}}
