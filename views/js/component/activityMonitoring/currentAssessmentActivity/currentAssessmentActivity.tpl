<div>
    <div class="col-4 dashboard-block">
        <h4>
            <span class="icon icon-play"></span>
            {{ assessments.current.label }}
        </h4>
        <span class="dashboard-block-number {{ assessments.current.container }}">
            {{ assessments.current.value }}
        </span>
    </div>

    <div class="col-2 dashboard-block">
        <h4>
            <span class="icon icon-play"></span>
            {{ assessments.inProgress.label }}
        </h4>
        <span class="dashboard-block-number {{ assessments.inProgress.container }}">
            {{ assessments.inProgress.value }}
        </span>
    </div>

    <div class="col-2 dashboard-block">
        <h4>
            <span class="icon icon-time"></span>
            {{ assessments.awaiting.label }}
        </h4>
        <span class="dashboard-block-number {{ assessments.awaiting.container }}">
            {{ assessments.awaiting.value }}
        </span>
    </div>

    <div class="col-2 dashboard-block">
        <h4>
            <span class="icon icon-continue"></span>
            {{ assessments.authorized.label }}
        </h4>
        <span class="dashboard-block-number {{ assessments.authorized.container }}">
            {{ assessments.authorized.value }}
        </span>
    </div>

    <div class="col-2 dashboard-block">
        <h4>
            <span class="icon icon-pause"></span>
            {{ assessments.paused.label }}
        </h4>
        <span class="dashboard-block-number {{ assessments.paused.container }}">
            {{ assessments.paused.value }}
        </span>
    </div>
</div>