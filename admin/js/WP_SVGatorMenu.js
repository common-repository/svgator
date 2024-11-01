function WP_SVGatorNotice($) {
    const DEFAULT_TIMEOUT = 10000;

    let timeout = false;
    let $dom;

    function init() {
        let $container = $('#wp-svgator-notice');
        $dom = {
            container: $container,
            message: $container.find('.wp-svgator-notice-message'),
            close: $container.find('.wp-svgator-notice-close'),
        };

        $dom.close.on('click', function() {
            close();
        });
    }

    function close() {
        $dom.container.removeClass('open');
    }

    function show(params) {
        $dom.message.text(params.msg);
        $dom.container.addClass('open');
        $dom.container.attr('data-type', params.type || '');

        if (timeout) {
            window.clearTimeout(timeout);
        }

        timeout = window.setTimeout(function() {
            close();
            timeout = false;
        }, params.timeout || DEFAULT_TIMEOUT);
    }

    this.show = show;

    init();
}

class WP_SVGator {
    _logoutNonce = null;
    _loginNonce = null;
    _importProjectNonce = null;
    projectsPerPage = 15;

    constructor($, SVGator) {
        this.$ = $;

        if (typeof SVGator === 'undefined') {
            throw 'SVGator is not loaded.';
        }

        this.SVGator = SVGator;
        this.notice = new WP_SVGatorNotice($);

        this.init();
    }

    init() {
        let slf = this;
        this._logoutNonce = slf.$('#svgator-header input[name="svgator_logOut_nonce"]').val();
        this._loginNonce = slf.$('#login-container input[name="svgator_logIn_nonce"]').val();

        slf.$('#svgator-filter').on('submit', function(e) {
            e.preventDefault();
        });

        slf.$('#svgator-filter [type="search"]').on('keyup search', function(e) {
            slf.filterProjects(e.target.value);
        });

        slf.$('#login-to-svgator').on('click', function(e) {
            e.preventDefault();

            slf.updateAuthToken().then(function() {
                let pr = slf.loadProjects();
                if (!pr) {
                    return;
                }

                pr.then(function(data) {
                    slf.updateExports(data && data.limits && data.limits.exports);
                    slf.listProjects(data.response);
                    slf._importProjectNonce = data.nonce;
                }).catch(function(err) {
                    slf.notice.show({
                        msg: (err && err.error) || 'Failed to load projects',
                        type: 'error',
                    });

                    if (err.error === 'Failed to load projects. Please try to log in again.') {
                        slf.logout();
                    }
                });
            }).catch(function(json) {
                let msg = json && json.error || json;
                slf.notice.show({
                    msg,
                    type: 'error',
                });
            });
        });

        slf.$('#svgator-header .logout').on('click', function(e) {
            e.preventDefault();

            let pr = slf.logout();

            pr.finally(function() {
                slf.paginateVisibleProjects();
            });
        });
    }

    filterProjects(search) {
        let slf = this;
        search = slf.$.trim(search).toLowerCase();
        slf.$('#svgator-projects .svgator-no-project').remove();

        let $lis = slf.$('#svgator-projects li');
        if (!search) {
            $lis.addClass('on-filter');
            slf.paginateVisibleProjects();
            return;
        }

        $lis.removeClass('on-filter');

        $lis.each(function(idx, li) {
            let $li = slf.$(li);
            let liText = $li.find('.svgator-title-container').text().toLowerCase();

            if (liText.indexOf(search) >= 0) {
                $li.addClass('on-filter');
            }
        });

        if ($lis.filter('.on-filter').length === 0) {
            let $noProjects = slf.$('<div>')
                .addClass('svgator-no-project')
                .text('No project found');

            slf.$('#svgator-projects').append($noProjects);
        }

        slf.paginateVisibleProjects();
    }

    paginateVisibleProjects() {
        let slf = this;
        let $projects = slf.$('#svgator-projects li');
        $projects.removeClass('on-page');

        $projects = $projects.filter('.on-filter');

        let $paginationContainer = slf.$('#svgator-projects-pagination');
        $paginationContainer.empty();

        let $ul = slf.$('<ul>');

        for (let i = 0; i < $projects.length; i++) {
            let page = parseInt(i / slf.projectsPerPage) + 1;
            slf.$($projects[i]).attr('data-page', page);

            if (!$ul.find('[data-page="' + page + '"]').length) {
                let $li = slf.$('<li>')
                    .attr('data-page', page)
                    .text(page);

                $li.on('click', function() {
                    $projects.removeClass('on-page');
                    $projects.filter('[data-page="' + page + '"]').addClass('on-page');
                    $li.siblings().removeClass('active');
                    $li.addClass('active');
                });

                $ul.append($li);
            }
        }

        $paginationContainer.append($ul);

        $ul.find('> li').first().click();
    }

    logout() {
        let slf = this;
        return slf.makeRequest({
            'action': 'svgator_logOut',
            'svgator_logOut_nonce': this._logoutNonce,
        }).then(function() {
            slf.notice.show({
                msg: 'Logged out successfully.',
            });

            slf.$('.svgator-wrap').removeClass('logged-in').addClass('logged-out');

            slf.$('#svgator-projects').empty();
        }).catch(function(err) {
            slf.notice.show({
                msg: err && err.error || 'Failed to log out.',
                type: 'error',
            });
            slf.$('#svgator-projects').empty();
        });
    }

    updateAuthToken() {
        let slf = this;
        return new Promise(function(resolve, reject) {

            const host = ['wp.local', 'localhost'].includes(location.hostname) || location.hostname.includes('.svgator.net')
                ? 'https://app.svgator.net/app-auth'
                : 'https://app.svgator.com/app-auth';

            let endpoint = window.svgator_options
                && window.svgator_options.endpoint
                || host;

            slf.SVGator.auth(
                'dynamic',
                false,
                endpoint,
            ).then(function(resp) {
                if (!resp || !resp.auth_code || !resp.app_id) {
                    if (reject) {
                        reject(resp);
                    }
                    return;
                }

                slf.saveToken(resp)
                    .then(function(data) {
                        if (!data || !data.success) {
                            reject(data && data.error || data);
                        } else {
                            slf.$('.svgator-wrap').removeClass('logged-out').addClass('logged-in');

                            if (resolve) {
                                resolve(data);
                            }
                        }
                    })
                    .catch(function(data) {
                        if (reject) {
                            reject(data);
                        }
                    });
            }).catch(function(err) {
                let eMsg = 'Failed to connect to SVGator.';
                if (err) {
                    if (err.msg) {
                        if (typeof err.msg === 'string') {
                            eMsg = err.msg.toString();
                        } else {
                            try {
                                eMsg = JSON.stringify(err.msg);
                            } catch (e) {
                                if (err.msg.toString) {
                                    eMsg = err.msg.toString();
                                }
                            }
                        }
                    }

                    if (err.code) {
                        eMsg += ' (' + err.code + ')';
                    }
                }

                reject(eMsg);
            });
        });
    }

    saveToken(data) {
        return this.makeRequest({
            'action': 'svgator_saveToken',
            'svgator_logIn_nonce': this._loginNonce,
            'auth_code': data.auth_code,
            'app_id': data.app_id,
        });
    }

    loadProjects() {
        return this.makeRequest({
            'action': 'svgator_getProjects',
        });
    }

    updateExports(exports) {
        if (!exports || !exports.limit) {
            return false;
        }
        let $cont = this.$('.svgator-limits');
        $cont.find('.used').text(exports.used);
        $cont.find('.limit').text(exports.limit);
        $cont.show();
        return true;
    }

    listProjects(projects) {
        let slf = this;
        let $projectsContainer = slf.$('#svgator-projects');

        if (!$projectsContainer.length) {
            return;
        }

        let $ul = slf.$('<ul>');

        for (let i = 0; i < projects.length; i++) {
            let project = projects[i];

            let $li = slf.$('<li>');

            project.$dom = $li;

            let $projectImgContainer = slf.$('<div>')
                .addClass('svgator-preview-container')
                .css({
                    backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.5)), url('${project.preview}')`,
                });

            let $projectTitleContainer = slf.$('<div>')
                .addClass('svgator-title-container')
                .text(project.title);
            $projectImgContainer.append($projectTitleContainer);

            let $projectCommandsContainer = slf.$('<div>')
                .addClass('svgator-commands-container');
            let $projectImport = slf.$('<span>')
                .text('IMPORT TO MEDIA')
                .addClass('svgator-commands-import')
                .on('click', function(e) {
                    e.preventDefault();

                    slf.importProject(project);
                });
            $projectCommandsContainer.append($projectImport);

            $li.append($projectImgContainer);
            $li.append($projectCommandsContainer);

            $ul.append($li);
        }

        $projectsContainer.append($ul);

        slf.filterProjects(slf.$('#svgator-filter [type="search"]').val());
        slf.paginateVisibleProjects();
    }

    importProject(project) {
        let slf = this;
        slf.makeRequest({
            'action': 'svgator_importProject',
            'project_id': project.id,
            'svgator_importProject_nonce': slf._importProjectNonce,
        }, {
            hoverContainer: project.$dom,
        }).then(function(data) {
            const exports = data
                && data.response
                && data.response.limits
                && data.response.limits.exports;

            slf.updateExports(exports);
            slf.notice.show({
                msg: 'Project imported successfully.',
            });
        }).catch(function(error) {
            const exports = error
                && error.data
                && error.data.limits
                && error.data.limits.exports;

            slf.updateExports(exports);
            slf.notice.show({
                msg: error && error.error
                    ? error.error
                    : 'Failed to import project. Try logging out and back in again!',
                type: 'error',
            });
        });
    }

    makeRequest(data, options) {
        let slf = this;
        return new Promise(function(resolve, reject) {
            let $loader = slf.$('<div>').addClass('svgator-loader');
            if (options && options.hoverContainer) {
                options.hoverContainer.append($loader);
            } else {
                slf.$('#wpcontent').append($loader);
            }

            slf.$.post(
                'admin-ajax.php',
                data,
                null,
                'json',
            ).done(function(data) {
                if (data && data.success === false) {
                    if (reject) {
                        reject.call(slf, data);
                        return;
                    }
                }

                if (resolve) {
                    resolve.call(slf, data);
                }
            }).fail(function() {
                if (reject) {
                    reject.call(slf, data);
                }
            }).always(function() {
                $loader.remove();
            });
        });
    }
}

window.jQuery(function() {
    let wpSvgator = new WP_SVGator(jQuery, SVGator);

    if (jQuery('.svgator-wrap.logged-in').length) {
        let pr = wpSvgator.loadProjects();
        if (!pr) {
            return;
        }

        pr.then(function(data) {
            wpSvgator.updateExports(data.limits && data.limits.exports);
            wpSvgator.listProjects(data.response);
            wpSvgator._importProjectNonce = data.nonce;
        }).catch(function(err) {
            wpSvgator.notice.show({
                msg: (err && err.error) || 'Failed to load projects',
                type: 'error',
            });

            if (err.error === 'Failed to load projects. Please try to log in again.') {
                wpSvgator.logout();
            }
        });
    }
});
