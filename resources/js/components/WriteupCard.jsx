import { Link } from '@inertiajs/react';
import DifficultyBadge from './DifficultyBadge.jsx';
import { EyeIcon } from './Icons.jsx';

const dateFmt = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeZone: 'UTC' });
const numFmt = new Intl.NumberFormat('id-ID');
const MAX_TAGS = 3;

export default function WriteupCard({ writeup: w }) {
  const tags = [
    ...w.challengeTags.map((t) => ({ key: `c-${t}`, label: `#${t}` })),
    ...w.toolTags.map((t) => ({ key: `t-${t}`, label: t })),
  ];
  const extra = tags.length - MAX_TAGS;
  const { username, avatar } = w.author;

  return (
    <article className="card">
      <div className="card__top">
        <span className="eyebrow">{w.category}</span>
        <DifficultyBadge difficulty={w.difficulty} />
      </div>

      <h3 className="card__title clamp-2">
        <Link className="card__link" href={`/writeup/${w.slug}`}>{w.title}</Link>
      </h3>
      <p className="card__excerpt clamp-3">{w.excerpt}</p>

      <div className="card__bottom">
        {tags.length > 0 && (
          <ul className="tags" aria-label="Tag">
            {tags.slice(0, MAX_TAGS).map((t) => (
              <li key={t.key} className="tag" translate="no">{t.label}</li>
            ))}
            {extra > 0 && (
              <li className="tag tag--more" aria-label={`${extra} tag lainnya`}>+{extra}</li>
            )}
          </ul>
        )}

        <div className="card__foot">
          <div className="author">
            {avatar ? (
              <img className="avatar" src={avatar} width="32" height="32" loading="lazy" alt="" />
            ) : (
              <span className="avatar" aria-hidden="true">{username.slice(0, 2).toUpperCase()}</span>
            )}
            <div className="author__text">
              <span className="author__name" translate="no">{username}</span>
              <time className="meta" dateTime={w.createdAt}>{dateFmt.format(new Date(w.createdAt))}</time>
            </div>
          </div>
          <span className="meta views">
            <EyeIcon />
            <span>{numFmt.format(w.views)}&nbsp;views</span>
          </span>
        </div>
      </div>
    </article>
  );
}