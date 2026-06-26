@once
    <script>
        window.expandInventoryCountGroups = function (titles, expand) {
            const tableRoot = document.querySelector('.inventory-count-lines-table [x-data*="areGroupsCollapsedByDefault"]')
                ?? document.querySelector('[x-data*="areGroupsCollapsedByDefault"]');

            if (!tableRoot || !tableRoot._x_dataStack?.length) {
                return;
            }

            const data = tableRoot._x_dataStack[0];

            if (!data || typeof data.groupVisibility === 'undefined') {
                return;
            }

            if (expand) {
                titles.forEach((title) => {
                    if (!data.groupVisibility.includes(title)) {
                        data.groupVisibility.push(title);
                    }
                });

                return;
            }

            data.groupVisibility = [];
        };
    </script>
@endonce
