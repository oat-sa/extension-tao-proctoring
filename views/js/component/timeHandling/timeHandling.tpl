<div class="bulk-action-popup">
    <h2 class="title">{{__ "Action"}}: {{actionName}}</h2>

    {{#if allowedResources.length}}
        <div class="multiple">
            <p>{{__ "The action will be applied to the following session(s)"}}:</p>
            <ul class="plain applicables">
                {{#if changeTimeMode}}
                    {{#each allowedResources}}
                        <li data-resource="{{id}}">
                            <span class="resource-label">{{label}}</span>
                        </li>
                    {{/each}}
                {{else}}
                    {{#each allowedResources}}
                        <li data-resource="{{id}}">
                            <span class="resource-label">{{label}}</span>
                            {{#if remainingStr}}<span class="remaining">{{remainingStr}}</span>{{/if}}
                            {{#if extraTimeStr}}<span class="time">({{extraTimeStr}})</span>{{/if}}
                        </li>
                    {{/each}}
                {{/if}}
            </ul>
        </div>

        {{#if deniedResources.length}}
            <p>{{__ "However, the action will not be applied to the following sessions"}}:</p>
            <ul class="plain no-applicables">
                {{#each deniedResources}}
                <li data-resource="{{id}}">
                    <span class="resource-label">{{label}}</span>
                    <span class="reason">({{reason}})</span>
                </li>
                {{/each}}
            </ul>
        {{/if}}

        <div class="form">
            <p>
                {{#if changeTimeMode}}
                    <label for="input-extra-time">{{inputLabel}}:</label>
                    <span class="change-time-controls-container">
                        <label class="change-time-control-container">
                            <input class="change-time-control__input" checked="checked" type="radio" name="changeTimeControl" value="">
                            <i class="change-time-control--add">+</i>
                        </label>
                        <label class="change-time-control-container">
                            <input class="change-time-control__input" type="radio" name="changeTimeControl" value="-">
                            <i class="change-time-control--sub">—</i>
                        </label>
                    </span>
                    <input type="number" id="input-extra-time" data-control="time" value="{{time}}" step="1" min="0" />
                    <label for="input-extra-time">{{__ "minutes"}}</label>
                {{else}}
                    <label for="input-extra-time">{{inputLabel}}:</label>
                    <input type="text" id="input-extra-time" data-control="time" value="{{time}}" maxlength="4" size="4" />
                    <label for="input-extra-time">{{__ "minutes"}}</label>
                {{/if}}
                <div class="errors">
                    <div class="feedback-error small hidden">{{errorMessage}}</div>
                </div>
            </p>
        </div>
        <p>
            <strong>{{__ "Note"}}:</strong>
            <em>{{note}}</em>
        </p>

        {{#if reason}}
            <div class="reason">
                <p>
                    {{__ "Please provide a reason"}}:
                </p>
                <div class="categories"></div>
                <div class="comment">
                    <textarea placeholder="{{__ "comment..."}}"></textarea>
                </div>
            </div>
        {{/if}}

    {{else}}
        <p>{{__ "The action cannot be applied, no eligible sessions found"}}</p>
    {{/if}}

    <div class="actions">
        <button class="btn btn-info small action done" data-control="done">{{__ "OK"}}</button>
        {{#if allowedResources.length}}
            <a href="#" class="btn action cancel" title="{{__ 'cancel the action'}}" data-control="cancel">{{__ "cancel"}}</a>
        {{/if}}
    </div>

</div>
