<div class="eligibility-editor{{#if editingMode}} editing{{/if}}">
    <h2 class="title">{{title}}</h2>

    <div class="flex-grid ">
        <div class="{{#if editingMode}}flex-col-12{{else}}flex-col-6{{/if}} tree-container eligible-delivery">
            <label>{{__ 'Eligible Deliveries'}}</label>
                <div id="{{deliveryTreeId}}"></div>
        </div>
        <div class="{{#if editingMode}}flex-col-12{{else}}flex-col-6{{/if}} tree-container eligible-testTaker">
            <label>{{__ 'Eligible Test Takers'}} {{#if editingMode}}{{__ 'for'}} <span class="delivery-name">{{deliveryName}}</span> :{{/if}}</label>
                <div id="{{subjectTreeId}}"></div>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-info small done">{{__ "OK"}}</button>
        <a href="#" class="btn cancel" title="{{__ "cancel the action"}}">{{__ "cancel"}}</a>
    </div>
</div>
