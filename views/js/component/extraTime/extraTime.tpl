<div class="bulk-action-popup">
    <h2 class="title">{{__ "Action"}}: {{actionName}}</h2>

    {{#if allowedResources.length}}
    <div class="multiple">
        <p>{{__ "The action will be applied to the following test takers"}}:</p>
        <ul class="plain applicables">
            {{#each allowedResources}}
            <li data-resource="{{id}}">
                <span class="resource-label">{{label}}</span>
                {{#if remaining}}<span class="remaining">{{remaining}}</span>{{/if}}
                {{#if extraTime}}<span class="time">({{consumedTime}}/{{extraTime}} {{__ "minutes more"}})</span>{{/if}}
            </li>
            {{/each}}
        </ul>
    </div>

    {{#if deniedResources.length}}
    <p>{{__ "However, the action will not be applied to the following test takers"}}:</p>
    <ul class="plain no-applicables">
        {{#each deniedResources}}
        <li data-resource="{{id}}">
            <span class="resource-label">{{label}}</span>
            <span class="reason">({{reason}})</span>
        </li>
        {{/each}}
    </ul>
    {{/if}}

    {{else}}
    <p>{{__ "The action cannot be applied, no eligible test takers found"}}</p>
    {{/if}}

    <div class="form">
        <p>
            <label for="input-extra-time">{{__ "Extra time"}}:</label>
            <input id="input-extra-time" data-control="time" value="{{time}}" maxlength="4" size="4" />
            <label for="input-extra-time">{{__ "minutes"}}</label>
        </p>
    </div>
    <p>
        <strong>{{__ "Note"}}:</strong>
        <em>{{__ "the already granted time will be replaced by the new value"}}</em>
    </p>

    <div class="actions">
        <div class="feedback-error small hidden">{{__ "The extra time must be a number"}}</div>
        <button class="btn btn-info small action done" data-control="done">{{__ "OK"}}</button>
        <a href="#" class="btn action cancel" title="{{__ 'cancel the action'}}" data-control="cancel">{{__ "cancel"}}</a>
    </div>

</div>
