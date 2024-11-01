/**
 * @author Tibor Vincze
 * @date 12/7/2020 12:11 PM
 */

(function(wp, win, $) {

    let SVGatorNotice = function() {
        const DEFAULT_TIMEOUT = 10000;
        let timeout = false;
        let $dom;

        function init () {
            let $container = $('<div>')
                .attr('id', 'wp-svgator-notice');
            $message = $('<div>')
                .addClass('wp-svgator-notice-message');
            $close = $('<span>')
                .text('x')
                .addClass('wp-svgator-notice-close');
            $container
                .append($message)
                .append($close);
            $dom = {
                container: $container,
                message: $container.find('.wp-svgator-notice-message'),
                close: $container.find('.wp-svgator-notice-close'),
            };
            $dom.close.on('click', function() {
                close();
            })
        }

        function close() {
            $dom.container.removeClass('open');
        }

        function show (params, $target) {
            $dom.message.text(params.msg);
            $dom.container.addClass('open');
            $dom.container.attr('data-type', params.type || '');
            if ($target) {
                $target.append($dom.container);
            }

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
    };

    let SVGatorMedia = function(options) {
        'use strict';
        if (!options.onSelect || !options.onSelect.call) {
            throw "onSelect must be a function";
        }

        let frame;

        let $importView = $("<div>");
        let notice = new SVGatorNotice();
        let importer = new SVGatorImporter($importView, {
            onCancel: function(err) {
                const exports = (err && err.data && err.data.limits && err.data.limits.exports) ||
                    (err && err.response && err.response.limits && err.response.limits.exports);
                importer.updateExportLimits(exports);
                const msg = err && err.error || 'Unknown error.';
                notice.show({
                    msg,
                    type: 'error'
                }, $importView);
            },
            onSelect: function(resp) {
                const exports = resp && resp.limits && resp.limits.exports;
                importer.updateExportLimits(exports);
                options.onSelect(resp.attachment);
                frame.close();
            },
        });

        let SVGatorFrameClass = wp.media.view.MediaFrame.Post.extend({
            browseRouter: function(routerView) {
                routerView.set({
                    browse: {
                        text: wp.media.view.l10n.mediaLibraryTitle,
                        priority: 40
                    },
                    svgatorImport: {
                        text: "Import from SVGator",
                        priority: 20
                    },
                });
            },
            createStates: function() {
                this.states.add([
                    new wp.media.controller.Library({
                        autoSelect: true,
                        content: "upload",
                        contentUserSetting: true,
                        describe: false,
                        menu: "gallery",
                        router: "browse",
                        searchable: true,
                        sortable: true,
                        syncSelection: true,


                        id: 'insert',
                        title: 'Animated SVGs',
                        priority: 20,
                        toolbar: 'main-insert',
                        filterable: 'all',
                        library: wp.media.query(_.defaults({
                            post_mime_type: 'svgator/svg+xml',
                        }, this.options.library)),
                        multiple: false,
                        editable: false,
                        allowLocalEdits: false,
                        displaySettings: false,
                        displayUserSettings: false,
                    }),
                ]);
            },
        });

        function select() {
            let attachment = frame.state('insert').get('selection').first().toJSON();
            options.onSelect(attachment);
        }

        function svgatorImport(contentRegion) {
            let SVGatorLibraryView = wp.media.View.extend({
                tagName: 'div',
                className: 'svgator-library',
                id: 'svgator-library',
                attributes: {},
                events: {},
                initialize: function() {
                },
                render: function() {
                    if (this.$el) {
                        this.$el.html('').append($importView);
                        importer.open();
                    }
                    return this;
                },
            });

            contentRegion.view = new SVGatorLibraryView({
                controller: frame,
            }).render();
        }

        function setOptions(newOptions) {
            if (!newOptions) {
                return;
            }
            Object.keys(newOptions).forEach(function(key) {
                options[key] = newOptions[key];
            });
        }

        function open(options = {}) {
            setOptions(options);
            frame.open();
        }

        frame = new SVGatorFrameClass();

        frame.on('select', select);
        frame.on('insert', select);
        frame.on('content:create:svgatorImport', svgatorImport);
        frame.on('attach', function(){
            frame.$el.find('#menu-item-browse').click();
        });

        this.open = open;
    }

    let SVGatorImporter = function($cont, options) {
        let _importProjectsNonce = null;

        $cont = $($cont);
        $cont.html('');

        if (!options) {
            options = {};
        }

        let projectsPerPage = 40;

        let cachedResult;

        function open() {
            $cont.html('');
            let $innerDiv = $('<div>')
                .addClass('svgator-popup-inner')
                .addClass('svgator-loading');

            let $header = getPopupHeader();

            let $paginator = $('<div>')
                .attr('id', 'svgator-projects-pagination');

            let $ul = $('<ul class="svgator-project-list">');

            let pr = loadProjects();
            if (!pr) {
                return false;
            }

            pr.then(function(data) {
                if (!data || !data.success) {
                    return false;
                }

                _importProjectsNonce = data.nonce;

                cachedResult = data;

                const exports = data && data.limits && data.limits.exports;
                let $limits = getExportLimits(exports);
                if ($limits) {
                    $header.append($limits);
                }

                data.response.forEach(function(project) {
                    $ul.append(buildProjectLi(project));
                });

                $innerDiv.removeClass('svgator-loading');

                filterProjects($cont.find('#svgator-header [type="search"]').val());
            }).catch(function(err) {
                console.log(err);
                close(err);
            });

            $innerDiv.append($header);
            $innerDiv.append($ul);
            $innerDiv.prepend($paginator);
            $cont.append($innerDiv);
        }

        function getExportLimits(exportLimits) {
            if (!exportLimits || !exportLimits.limit) {
                return;
            }
            let $limits = $('<div class="svgator-limits">' +
                '<strong class="used">' + exportLimits.used + '</strong>' +
                '/' +
                '<strong class="limit">' + exportLimits.limit + '</strong>' +
                ' exports used. ' +
                '<a href="https://app.svgator.com/pricing#/" target="_blank"><strong>Upgrade now</strong></a>' +
                ' for unlimited exports.' +
                '</div>');
            return $limits;
        }

        function updateExportLimits(exportLimits) {
            if (!exportLimits || !exportLimits.limit) {
                return;
            }
            $cont.find('.svgator-limits .used').text(exportLimits.used);
            $cont.find('.svgator-limits .limit').text(exportLimits.limit);
            return true;
        }

        function getPopupHeader () {
            let $header = $('<div>')
                .attr('id', 'svgator-header');

            let $filter = $('<form>');
            let $input = $('<input>')
                .attr('type', 'search')
                .attr('placeholder', 'Search project...')
                .on('keyup search', function(e) {
                    filterProjects(e.target.value);
                });

            $filter.append($input);
            $header.append($filter);
            return $header;
        }

        function filterProjects (search) {
            search = $.trim(search).toLowerCase();
            $cont.find('.svgator-popup-inner .svgator-no-project').remove();

            let $lis = $cont.find('.svgator-popup-inner > ul li');
            if (!search) {
                $lis.addClass('svgator-on-filter');
                paginateProjects();
                return;
            }

            $lis.removeClass('svgator-on-filter');

            $lis.each(function(idx, li) {
                let $li = $(li);
                let liText = $li.find('.svgator-title-container').text().toLowerCase();

                if (liText.indexOf(search) >= 0) {
                    $li.addClass('svgator-on-filter');
                }
            });

            if ($lis.filter('.svgator-on-filter').length === 0) {
                let $noProjects = $('<div>')
                    .addClass('svgator-no-project')
                    .text('No project found');

                $cont.find('.svgator-popup-inner').append($noProjects);
            }

            paginateProjects();
        }

        function selectProject (project) {
            let $innerDiv = $cont.find('.svgator-popup-inner');
            $innerDiv.addClass('svgator-loading');

            let pr = importProject(project);
            if (!pr) {
                return close('No Promise generated.');
            }

            pr.then(function(data) {
                if (!data || !data.success) {
                    return close(data);
                }

                return select(data.response);
            }).catch(function(err) {
                return close(err);
            }).finally(function(){
                $innerDiv.removeClass('svgator-loading');
            });
        }

        function select(resp){
            options.onSelect && options.onSelect.call && options.onSelect(resp);
        }

        function close(resp) {
            options.onCancel && options.onCancel.call && options.onCancel(resp);
        }

        function importProject(project) {
            return makeRequest({
                'action': 'svgator_importProject',
                'svgator_importProject_nonce': _importProjectsNonce,
                'project_id': project.id,
            }, {
                hoverContainer: project.$dom,
            });
        }

        function buildProjectLi(project) {

            let $img = $('<img src="" class="icon" draggable="false">').attr('src', project.preview);
            let $li = $('<li class="attachment"><div class="attachment-preview"><div class="thumbnail"><div class="centered"></div><div class="filename"><div class="name"></div></div></div></div></li>');

            $li.find('.name').text(project.title);
            $li.find('.centered').append($img);

            $li.on('click', function() {
                selectProject(project);
            });

            return $li;
        }

        function paginateProjects() {
            let $projects = $cont.find('.svgator-popup-inner li');
            $projects.removeClass('svgator-on-page');

            $projects = $projects.filter('.svgator-on-filter');

            let $paginationContainer = $('#svgator-projects-pagination');
            $paginationContainer.empty();

            let $ul = $('<ul>');

            for (let i = 0; i < $projects.length; i++) {
                let page = Math.floor(i / projectsPerPage) + 1;
                $($projects[i]).attr('data-page', page);

                if (!$ul.find('[data-page="' + page + '"]').length) {
                    let $li = $('<li>')
                        .attr('data-page', page)
                        .text(page);

                    $li.on('click', function() {
                        $projects.removeClass('svgator-on-page');
                        $projects.filter('[data-page="' + page + '"]').addClass('svgator-on-page');
                        $li.siblings().removeClass('active');
                        $li.addClass('active');
                    });

                    $ul.append($li);
                }
            }

            $paginationContainer.append($ul);

            $ul.find('> li').first().click();
        }

        function loadProjects() {
            let slf = this;

            if (cachedResult) {
                return new Promise(function(res) {
                    res.call(slf, cachedResult);
                });
            }

            return makeRequest({
                'action': 'svgator_getProjects',
            });
        }

        function makeRequest(data, options) {
            let slf = this;
            let resolve, reject;

            let $loader = $('<div>').addClass('svgator-loader');
            if (options && options.hoverContainer) {
                options.hoverContainer.append($loader);
            } else {
                $('#wpcontent').append($loader);
            }

            $.post(
                'admin-ajax.php',
                data,
                null,
                'json'
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

            return new Promise(function(res, rej) {
                resolve = res;
                reject = rej;
            });
        }

        this.open = open;
        this.updateExportLimits = updateExportLimits;
    };

    win.SVGatorMedia = SVGatorMedia;
})(wp, window, jQuery);
