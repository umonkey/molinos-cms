<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../template.xsl" />

  <xsl:template match="content[@name='preview' and @id]" mode="content">
    <xsl:apply-templates select="document(concat($api,'node/preview.xml?id=',@id))/*">
      <xsl:with-param name="nid" select="@id" />
    </xsl:apply-templates>
  </xsl:template>

  <!-- Обработка ошибок в XML API. -->
  <xsl:template match="error">
    <h3>Ошибка XML API</h3>
    <p>
      <xsl:value-of select="@message" />
    </p>
  </xsl:template>

  <!-- Вывод содержимого объекта. -->
  <xsl:template match="fields">
    <xsl:param name="nid" />
    <h2>
      <a href="{@list}">
        <xsl:value-of select="@typename" />
      </a>
      <xsl:text>: </xsl:text>
      <xsl:value-of select="@nodename" />
    </h2>

    <div id="rcol">
      <xsl:apply-templates select="document(concat($api,'node/actions.xml?id=',$nid,'&amp;from=',$back))/actions" />
      <xsl:if test="uid/@id">
        <div class="nodecmd">
          <h3>Об авторе</h3>
          <ul>
            <li>
              <a href="admin/node/{uid/@id}" class="user">
                <xsl:value-of select="uid/fullname" />
                <xsl:if test="not(uid/fullname)">
                  <xsl:value-of select="uid/@name" />
                </xsl:if>
              </a>
            </li>
            <li>
              <a href="admin/content/list?search=uid%3A{uid/@id}">Найти все его документы</a>
            </li>
            <li>
              <a href="admin/content/list/{@class}?search=uid%3A{uid/@id}">Все его документы этого типа</a>
            </li>
          </ul>
        </div>
      </xsl:if>
    </div>

    <xsl:if test="field">
      <table id="preview">
        <tbody>
          <xsl:for-each select="field">
            <tr>
              <th>
                <xsl:value-of select="@title" />
                <xsl:text>:</xsl:text>
              </th>
              <td>
                <xsl:if test="@editable">
                  <a href="admin/edit/{$nid}/{@name}?destination={$back}" class="edit">
                    <img src="lib/modules/admin/styles/admin/images/icons/icon-edit.png" />
                  </a>
                </xsl:if>
              </td>
              <td>
                <xsl:if test="value/@class">
                  <xsl:attribute name="class">
                    <xsl:value-of select="value/@class" />
                  </xsl:attribute>
                </xsl:if>
                <xsl:value-of select="value" disable-output-escaping="yes" />
              </td>
            </tr>
          </xsl:for-each>
        </tbody>

        <tfoot>
          <tr>
            <th/>
            <td/>
            <td>
              <form method="get" action="admin/edit/{$nid}?destination={$back}">
                <input type="submit" value="Отредактировать" />
              </form>
            </td>
          </tr>
        </tfoot>
      </table>
    </xsl:if>
  </xsl:template>

  <!-- Действия над документом. -->
  <xsl:template match="actions">
    <xsl:if test="action[@href]">
      <div class="nodecmd">
        <h3>Действия</h3>
        <ul>
          <xsl:for-each select="action[@name!='edit' and @href]">
            <li>
              <a href="{@href}">
                <xsl:value-of select="@title" />
              </a>
            </li>
          </xsl:for-each>
        </ul>
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>
