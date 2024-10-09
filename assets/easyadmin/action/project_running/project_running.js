let projectsRunningInstances={};
let projectsRunningInterval;
const projectRunningHandler = function (event) {
    document.querySelectorAll('a.project-running-action').forEach((item) => {
        if (item.classList.contains('project-running-action-processed')) {
            return;
        }
        const project = item.dataset.project;
        if (project) {
            projectsRunningInstances[project] = new ProjectRunning(item);
            item.classList.remove('visually-hidden');
        }
        item.classList.add('project-analyse-action-processed');
    });

    function checkRunning() {
        const request = new XMLHttpRequest();
        request.open('POST', '/project/check-running', false);
        request.setRequestHeader( "Content-Type", "application/json" );
        request.send(JSON.stringify(Object.keys(projectsRunningInstances)));
        if (request.status === 200) {
            const response = JSON.parse(request.response);
            return response?.result ?? false;
        }
        return false;
    }

    function intervalHandler() {
        const res = checkRunning();
        if (res !== false) {
            Object.keys(res).forEach(function (project) {
                const running = res[project];
                if (running === projectsRunningInstances[project].getIsRunning()) {
                    return;
                }
                projectsRunningInstances[project].setIsRunning(running);
                if (running) {
                    projectsRunningInstances[project].setRunningMode();
                }
                else {
                    projectsRunningInstances[project].setIdleMode();
                }
            });
        }
    }

    projectsRunningInterval = setInterval(intervalHandler, 10000);
    intervalHandler();
}

window.addEventListener('DOMContentLoaded', projectRunningHandler);
class ProjectRunning {
    #link;
    #icon;
    #isRunning;
    constructor (link) {
        this.#link = link;
        this.#icon = link.querySelector('i.action-icon');
        const self = this;
        this.#link.addEventListener('click', function (e) {
            e.preventDefault();
            self.#clickHandler();
        });
    }

    #clickHandler() {
        if (!this.#isRunning) {
            this.#launchAnalyse();
            this.setIsRunning(true);
            this.setRunningMode();
        }
    }
    #launchAnalyse() {
        const request = new XMLHttpRequest();
        request.open('GET', this.#link.dataset.href, false);
        request.send(null);
        if (request.status === 200) {
            const response = JSON.parse(request.response);
            return response?.result ?? false;
        }
        return false;
    }

    setIsRunning(isRunning) {
        this.#isRunning = isRunning;
    }

    getIsRunning() {
        return this.#isRunning;
    }

    setIdleMode() {
        this.#icon.classList.add('fa-play');
        this.#icon.classList.remove('fa-spin', 'fa-spinner');
        this.#icon.setAttribute('style', '');
    }
    setRunningMode() {
        this.#icon.classList.add('fa-spin', 'fa-spinner');
        this.#icon.classList.remove('fa-play');
        this.#icon.setAttribute('style', '--fa-animation-duration: 2s;');
    }

}
