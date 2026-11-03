import { useState } from 'react';
import { CATEGORIES, CHALLENGE_TAGS, DIFFICULTIES, TOOL_TAGS } from '../constants.js';

function ToggleGroup({ legend, options, isSelected, onToggle, mono = false }) {
  return (
    <fieldset className="group">
      <legend className="eyebrow">{legend}</legend>
      <div className="group__items">
        {options.map((o) => (
          <button
            key={o.value}
            type="button"
            className={`chip chip--toggle${mono ? ' mono' : ''}`}
            aria-pressed={isSelected(o.value)}
            onClick={() => onToggle(o.value)}
            translate={mono ? 'no' : undefined}
          >
            {o.label}
          </button>
        ))}
      </div>
    </fieldset>
  );
}

export default function FilterPanel({ filters, onChange, onToggleTag, onReset, hasActive, activeCount }) {
  const [open, setOpen] = useState(() => window.matchMedia('(min-width: 1024px)').matches);
  const { category, difficulty, tags } = filters;

  return (
    <div className="filters" id="filter">
      <div className="filters__bar">
        <button
          type="button"
          className="btn btn--outline"
          aria-expanded={open}
          aria-controls="filter-groups"
          onClick={() => setOpen((o) => !o)}
        >
          Filter{activeCount ? ` (${activeCount})` : ''}
        </button>
        <button type="button" className="btn btn--ghost" onClick={onReset} disabled={!hasActive}>
          Reset filter
        </button>
      </div>

      <div id="filter-groups" className="filters__groups" hidden={!open}>
        <ToggleGroup
          legend="Kategori"
          options={CATEGORIES.map((c) => ({ value: c, label: c }))}
          isSelected={(v) => category === v}
          onToggle={(v) => onChange({ category: category === v ? '' : v })}
        />
        <ToggleGroup
          legend="Level kesulitan"
          options={DIFFICULTIES}
          isSelected={(v) => difficulty === v}
          onToggle={(v) => onChange({ difficulty: difficulty === v ? '' : v })}
        />
        <ToggleGroup
          legend="Tag tantangan"
          mono
          options={CHALLENGE_TAGS.map((t) => ({ value: t, label: `#${t}` }))}
          isSelected={(v) => tags.includes(v)}
          onToggle={onToggleTag}
        />
        <ToggleGroup
          legend="Tag tools"
          mono
          options={TOOL_TAGS.map((t) => ({ value: t, label: t }))}
          isSelected={(v) => tags.includes(v)}
          onToggle={onToggleTag}
        />
      </div>
    </div>
  );
}