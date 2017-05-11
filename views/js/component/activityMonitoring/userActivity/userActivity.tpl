<div>
    <div class="col-6 dashboard-block">
        <span class="dashboard-block-number {{ activeTestTakers.container }}">
            {{ activeTestTakers.value }}
        </span>
        <h3>
            <span class="icon icon-test-takers"></span>
            {{ activeTestTakers.label }}
        </h3>
    </div>
    <div class="col-6 dashboard-block">
        <span class="dashboard-block-number {{ activeProctors.container }}">
            {{ activeProctors.value }}
        </span>
        <h3>
            <span class="icon icon-test-taker"></span>
            {{ activeProctors.label }}
        </h3>
    </div>
</div>