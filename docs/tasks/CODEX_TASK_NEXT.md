Read docs/CODEX.md and strictly follow it
(thin controllers, no DB queries in Blade, PANEL vs ADMIN separation).

TASK: Replace "feed background segments" on the animal weight chart with a 2nd line dataset that represents feed changes over time.
Goal: Make the chart readable even with many feed changes.

CRITICAL:
- Do NOT patch/overwrite files blindly. If you need to edit GetAnimalProfileQuery.php (or similar), OPEN the file and modify only the minimal required parts.
- If a referenced file is missing in the repo, stop and report exact missing path(s) + propose where to implement instead.
- No DB queries in Blade.

================================================
A) DATA MODEL / BUSINESS RULE
================================================
We have:
- animal_weights table (animal_id, weight, date)
- animal_feedings table (animal_id, feed_id, date, qty)
- feeds table (id, name, ...)

We want to overlay feed changes on the weight chart:
1) Load ALL weights for the animal sorted ASC by date.
2) Load ALL feedings for the animal sorted ASC by date.
3) Detect FEED CHANGE EVENTS:
   - Iterate feedings in ASC order
   - Keep last_feed_id
   - A "change event" happens when current.feed_id != last_feed_id (ignore duplicates)
   - Record events: {date, feed_id, feed_name}
4) Convert feed change events to a second chart dataset "FeedIndex":
   - Assign each distinct feed_id a stable numeric index:
     e.g. first seen feed => 50, next distinct feed => 100, next => 150, etc. (step=50)
     (Alternatively 1,2,3 but keep visible separation; prefer step 50)
   - Build a time series aligned to chart labels:
     Chart labels are the WEIGHT dates (the x-axis).
     For each weight date, determine which feed was active at that time:
       - last change event with event.date <= weight.date
       - if none, use the first feeding's feed (if exists) else null
     Put FeedIndex value for that date.
   - Result: feedIndexPoints array same length as weights array.
5) Display tooltip for feed line:
   - On hover of a weight point, tooltip shows:
     - Waga: X g
     - Karma w tym okresie: FEED_NAME (derived from the feed active at that date)

IMPORTANT:
- If there are NO feedings => do not render the feed dataset at all.
- If there are weights but feedings start later than first weight:
  before first feeding, feed line can be null (gaps) OR assume first feeding applies back. Choose:
  - Prefer null until first feeding date, so we do not invent data.

================================================
B) CHART.JS IMPLEMENTATION (UI)
================================================
Current chart shows the "Waga" dataset.
Modify it to include 2nd dataset:
- dataset 1: label "Waga", type "line", yAxisID "yWeight"
- dataset 2: label "Karma", type "line" (or "stepLine" / stepped: true), yAxisID "yFeed"

Scales:
- yWeight: left axis, grams as now
- yFeed: right axis, hidden ticks optionally BUT MUST keep it stable
  - Use tick callback to map numeric value -> feed name (optional)
  - Or hide ticks entirely to avoid clutter (preferred)
- Keep styling consistent with dark theme; do not change navbar/layout.

Tooltip:
- Custom tooltip callback:
  - If hovering "Waga" dataset point: show Waga + FeedName for that index.
  - If hovering "Karma" dataset point: show FeedName only (and maybe FeedIndex)
- Ensure tooltip always shows correct feed name for the hovered x index.

Visibility:
- Add legend entries "Waga" and "Karma".
- Keep "Karma" dataset subtle but clearly distinct (different stroke, dash or pointStyle).
- If too many feeds, colors are NOT required; numeric mapping handles it.

================================================
C) ARCHITECTURE / WHERE TO PLACE LOGIC
================================================
No DB in Blade.
Implement a service / query object:
- App/Services/Animal/AnimalWeightChartService.php
Responsibilities:
- fetchWeights(animalId)
- fetchFeedings(animalId)
- buildFeedChangeEvents(feedings)
- mapFeedIdsToIndices(events)
- buildFeedIndexSeries(weights, events, mapping)
Return DTO/ViewModel:
- labels (date strings)
- weightValues
- feedIndexValues (nullable)
- feedNameByIndex (array aligned to labels)  // for tooltips
- feedIndexMeta (mapping feed_id->index, index->feed_name)  // optional

Controller:
- panel animal profile controller uses this service
- pass chart VM into Blade

Blade:
- only consumes JSON blobs: labels, datasets, feedNameByIndex
- no loops doing DB, only rendering

================================================
D) SAFETY / REGRESSION
================================================
- Do not break the profile page render even if chart data missing.
- Ensure nulls in feedIndexValues do not crash chart (use spanGaps=false).
- No file should be overwritten entirely; only minimal edits.

================================================
E) DELIVERABLES
================================================
1) AnimalWeightChartService + DTO/VM
2) Controller supplies VM to view
3) Chart JS updated: second dataset + second y-axis + tooltip showing feed name
4) Remove/ignore background segment coloring logic (if currently exists) so it no longer tries to color spans.
5) If GetAnimalProfileQuery.php was previously damaged, restore it by re-creating the class from scratch based on current usage in the project:
   - Find all references to GetAnimalProfileQuery in code
   - Rebuild only the methods that are actually used (same signatures)
   - Keep it thin: delegate to repositories/services
   - Add tests or at least a manual verification checklist.

STOP CONDITION:
If you cannot access a needed file (missing from repo), report exact file paths and the compile/runtime error that would occur, then implement the same logic in a clearly correct alternative location and update references accordingly.
