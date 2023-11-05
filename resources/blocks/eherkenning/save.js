/**
 * Internal dependencies.
 */
import image from '../../../resources/img/logo-eherkenning.svg';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.css';

export default function Save({ attributes, setAttributes }) {
  const { redirectUrl } = attributes;

  return (
    <a className="sopenid-button-container" href={redirectUrl}>
        <img src={image} width="160" height="28" />
    </a>
  );
}
