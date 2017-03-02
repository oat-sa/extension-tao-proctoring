<span class="listbox-actions">
    {{#if cancelable}}
    <span class="action cancel js-cancel">
        <span class="icon-stop"></span>{{__ "Cancel"}}
    </span>
    {{/if}}
    <span class="action play js-proceed" data-delivery="{{id}}">
        <span class="icon-play"></span>{{__ "Proceed"}}
    </span>
</span>