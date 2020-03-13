<span class="listbox-actions">
    {{#if cancelable}}
    <span class="action cancel js-cancel" tabindex="0" role="button">
        <span class="action-control">
            <span class="icon-stop"></span>{{__ "Cancel"}}
        </span>
    </span>
    {{/if}}
    <span class="action play js-proceed" data-delivery="{{id}}" tabindex="0" role="button">
        <span class="action-control">
            <span class="icon-play"></span>{{__ "Proceed"}}
        </span>
    </span>
</span>
